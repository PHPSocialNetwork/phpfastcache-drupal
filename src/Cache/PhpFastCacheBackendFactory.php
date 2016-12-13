<?php
namespace Drupal\phpfastcache\Cache;

use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use phpFastCache\CacheManager;
use Drupal\Core\Database\Connection;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;

/**
 * Class PhpFastCacheService
 */
class PhpFastCacheBackendFactory implements \Drupal\Core\Cache\CacheFactoryInterface
{
    /**
     * @var ExtendedCacheItemPoolInterface
     */
    protected $cachePool;

    /**
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
        /**
         * We are currently in the border of the Drupal bootstrap
         * therefore autoload, and other mechanism function are not
         * yet loaded, so we have to hard-include the Pfc autoload here
         */
        define('PFC_IGNORE_COMPOSER_WARNING', true);
        require_once __DIR__ . '/../../phpfastcache-php/src/autoload.php';

        $this->backendClass = 'Drupal\phpfastcache\Cache\PhpFastCacheBackend';
        $this->connection = $connection;
        $this->settings = $this->getSettingsFromDatabase();
        $this->cachePool = $this->getPhpFastCacheInstance();

        if(!$this->settings['phpfastcache_enabled']){
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
                die('PhpFastCache is not enabled, please go to <strong>admin/config/development/phpfastcache</strong> then configure PhpFastCache.');
            }
            else
            {
                $this->backendClass = 'Drupal\phpfastcache\Cache\PhpFastCacheVoidBackend';
            }
        }
    }

  /**
   * @return \phpFastCache\Cache\ExtendedCacheItemPoolInterface
   */
    protected function getPhpFastCacheInstance()
    {
      $options = ['ignoreSymfonyNotice' => true];
      $driverName = $this->settings['phpfastcache_default_driver'];
      $driversConfig = $this->settings['phpfastcache_drivers_config'];

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
            $instance = CacheManager::getInstance($driverName, array_merge($options, [
              'path' => $driversConfig[$driverName]['path'],
              'securityKey' => $driversConfig[$driverName]['path']['security_key'],
            ]));
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
            $instance = CacheManager::getInstance($driverName, array_merge($options, [
              'host' => $driversConfig[$driverName]['host'],
              'port' => $driversConfig[$driverName]['port'],
              'password' => $driversConfig[$driverName]['password'],
              'timeout' => $driversConfig[$driverName]['timeout'],
              'dbindex' => $driversConfig[$driverName]['dbindex'],
            ]));
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
            $instance = CacheManager::getInstance('Devnull');
            \Drupal::logger('cache')->critical("Unable to retrieve a valid driver (got '{$driverName}'). Drupal is now working in degraded mode");
            break;
        }
      }catch(phpFastCacheDriverCheckException $e){
        $instance = CacheManager::getInstance('Devnull');
        \Drupal::logger('cache')->critical("The Driver '{$driverName}' failed to initialize with the following error {$e->getMessage()}. Drupal is now working in degraded mode");
      }

      return $instance;
    }

    /**
     * Get settings from database.
     * At this level of runtime execution
     * settings are not available yet.
     * @return array
     */
    protected function getSettingsFromDatabase()
    {
        $query = 'SELECT `data`
                  FROM {' . $this->connection->escapeTable('config') . '} 
                  WHERE `name` = :name
                  LIMIT 1';
        $params = [':name' => 'phpfastcache.settings'];
        $result = $this->connection->query($query, $params);

        return unserialize($result->fetchField());
    }

    /**
     * Gets ApcuBackend for the specified cache bin.
     *
     * @param $bin
     *   The cache bin for which the object is created.
     *
     * @return \Drupal\Core\Cache\ApcuBackend
     *   The cache backend object for the specified cache bin.
     */
    public function get($bin) {
        return new $this->backendClass($bin, $this->cachePool/*, $this->checksumProvider*/);
    }
}