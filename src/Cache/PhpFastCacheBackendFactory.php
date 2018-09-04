<?php
namespace Drupal\phpfastcache\Cache;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Database\Connection;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Phpfastcache\CacheManager;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;

/**
 * Class PhpFastCacheBackendFactory
 */
class PhpFastCacheBackendFactory implements CacheFactoryInterface
{
  const ENV_DEV = 'dev';
  const ENV_PROD = 'prod';

  /**
   * Cache collector for debug purposes
   * @var array
   */
    protected $cacheCollector = [];

    /**
     * @var ExtendedCacheItemPoolInterface
     */
    protected $cachePool;

    /**
     *
     * The PhpFastCache backend class to use.
     *
     * @var string
     */
    protected $backendClass;

    /**
     * The cache tags checksum provider.
     *
     * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
     */
    protected $settings;

    /**
     * The database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $connection;

    /**
     * PhpFastCacheBackendFactory constructor.
     * @param \Drupal\Core\Database\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->backendClass = PhpFastCacheBackend::class;
        $this->connection = $connection;
        $this->settings = $this->getSettingsFromDatabase();
        $this->cachePool = $this->getPhpFastCacheInstance();

        if(!$this->settings['phpfastcache_enabled']){

          /**
           * @todo Use route identifier
           */
            if(strpos($_SERVER['REQUEST_URI'], 'admin/config/development/phpfastcache') === false)
            {
                /**
                 * At this level nothing is efficient
                 * - drupal_set_message() is not working/displaying anything
                 * - throwing exception displays a fatal error without backtrace
                 * - echoing destroys header leading to another fatal error
                 *
                 * Let's dying miserably by showing a simple but efficient message
                 */
                if($this->settings['phpfastcache_env'] === self::ENV_DEV){
                  \Drupal::messenger()->addWarning('PhpFastCache is not enabled, please go to <strong>admin/config/development/phpfastcache</strong> then configure PhpFastCache or comment out the cache backend override in settings.php.');
                }else{
                  \Drupal::messenger()->addError('PhpFastCache is not enabled, please go to <strong>admin/config/development/phpfastcache</strong> then configure PhpFastCache or comment out the cache backend override in settings.php.');
                }
                $this->backendClass = PhpFastCacheVoidBackend::class;
            }
        }
    }

  /**
   * @return ExtendedCacheItemPoolInterface
   * @throws ServiceUnavailableHttpException
   */
    protected function getPhpFastCacheInstance() :ExtendedCacheItemPoolInterface
    {
      $error = false;
      /**
       * Global options
       */
      $options = [
        //'ignoreSymfonyNotice' => true,
        'defaultTtl' => $this->settings['phpfastcache_default_ttl'],
      ];

      $driverName = $this->settings['phpfastcache_default_driver'];
      $driversConfig = $this->settings['phpfastcache_drivers_config'];
      $configClass = sprintf('Phpfastcache\\Drivers\\%s\\Config', \ucfirst(\strtolower($driverName)));

      try{
        /**
         * Here goes the database settings link
         */
        switch($driverName)
        {
          /**
           * No-option drivers
           */
          case 'apc':
          case 'apcu':
          case 'wincache':
          case 'xcache':
          case 'zenddisk':
          case 'zendshm':
            $instance = CacheManager::getInstance($driverName, array_merge($options, []));
            break;
          /**
           * Option-required drivers
           */
          case 'couchbase':
            $instance = CacheManager::getInstance($driverName, array_merge($options, [
              'host' => $driversConfig[$driverName]['host'],
              'username' => $driversConfig[$driverName]['username'],
              'password' => $driversConfig[$driverName]['password'],
              'buckets' => [
                [
                  'bucket' => $driversConfig[$driverName]['bucket'],
                  'password' => $driversConfig[$driverName]['bucket_password'],
                ]
              ],
            ]));
            break;
          case 'files':
          case 'sqlite':
          case 'level':
            $instance = CacheManager::getInstance($driverName, new $configClass(array_merge($options, [
              'htaccess' => $this->settings['phpfastcache_htaccess'],
              'path' => $driversConfig[$driverName]['path'],
              'securityKey' => $driversConfig[$driverName]['security_key'],
            ])));
            break;
          case 'memcache':
          case 'memcached':
            $instance = CacheManager::getInstance($driverName, array_merge($options, [
              'host' => $driversConfig[$driverName]['host'],
              'port' => $driversConfig[$driverName]['port'],
              'sasl_username' => $driversConfig[$driverName]['sasl_username'],
              'sasl_password' => $driversConfig[$driverName]['sasl_password'],
            ]));
            break;
          case 'mongodb':
            $instance = CacheManager::getInstance($driverName, array_merge($options, [
              'host' => $driversConfig[$driverName]['host'],
              'port' => $driversConfig[$driverName]['port'],
              'username' => $driversConfig[$driverName]['username'],
              'password' => $driversConfig[$driverName]['password'],
              'timeout' => $driversConfig[$driverName]['timeout'],
            ]));
            break;
          case 'predis':
          case 'redis':
            $instance = CacheManager::getInstance($driverName, new $configClass(array_merge($options, [
              'host' => $driversConfig[$driverName]['host'],
              'port' => $driversConfig[$driverName]['port'],
              'password' => $driversConfig[$driverName]['password'],
              'timeout' => $driversConfig[$driverName]['timeout'],
              'database' => $driversConfig[$driverName]['dbindex'],
            ])));
            break;
          case 'ssdb':
            $instance = CacheManager::getInstance($driverName, array_merge($options, [
              'host' => $driversConfig[$driverName]['host'],
              'port' => $driversConfig[$driverName]['port'],
              'password' => $driversConfig[$driverName]['password'],
              'timeout' => $driversConfig[$driverName]['timeout'] * 1000,
            ]));
            break;
          /**
           * In case tha the Default driver
           * is not recognized set it to Devnull
           */
          default:
            $error = "Unable to retrieve a valid driver (got '{$driverName}').";
            $instance = CacheManager::getInstance('Devnull');
            break;
        }
      }catch(PhpfastcacheDriverCheckException $e){
        $error = "The '{$driverName}' driver failed to initialize with the following error: {$e->getMessage()}.";
        $instance = CacheManager::getInstance('Devnull');
      } catch(\Throwable $e){
        $error = "The '{$driverName}' driver encountered the following error: {$e->getMessage()}.";
        $instance = CacheManager::getInstance('Devnull');
      }

      if($error){
        throw new ServiceUnavailableHttpException(60, $error, $e ?? null);
      }

      return $instance;
    }

    /**
     * Get settings from database.
     * At this level of runtime execution
     * settings are not available yet.
     * @return array
     */
    protected function getSettingsFromDatabase(): array
    {
        $query = 'SELECT `data`
                  FROM {' . $this->connection->escapeTable('config') . '} 
                  WHERE `name` = :name
                  LIMIT 1';
        $params = [':name' => 'phpfastcache.settings'];
        $result = $this->connection->query($query, $params);

        return (array) unserialize($result->fetchField(), ['allowed_classes' => false]);
    }

    /**
     * Gets PhpFastCacheBackend for the specified cache bin.
     *
     * @param string $bin
     *   The cache bin for which the object is created.
     *
     * @return \Drupal\phpfastcache\Cache\PhpFastCacheBackend|\Drupal\phpfastcache\Cache\PhpFastCacheVoidBackend
     *   The cache backend object for the specified cache bin.
     */
    public function get($bin) {
        if(\in_array($bin, $this->settings['phpfastcache_bins'], true) || \in_array('default', $this->settings['phpfastcache_bins'], true)){
          return new $this->backendClass($bin, $this->cachePool, $this->settings);
        }else{
          return new PhpFastCacheVoidBackend($bin, $this->cachePool, $this->settings);
        }
    }
}
