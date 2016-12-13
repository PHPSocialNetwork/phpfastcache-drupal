<?php
namespace Drupal\phpfastcache\Cache;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use phpFastCache\CacheManager;
use Drupal\Core\Database\Connection;

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
         * Due to the low-level execution stack of PhpFastCacheBackend
         * we have to hard-include the PhpFastCache autoload here
         */
        require_once __DIR__ . '/../../phpfastcache-php/src/autoload.php';
        $this->backendClass = 'Drupal\phpfastcache\Cache\PhpFastCacheBackend';
        /*$this->checksumProvider = $checksum_provider;*/
        $this->cachePool = CacheManager::redis(['ignoreSymfonyNotice' => true]);
        $this->connection = $connection;
        $this->settings = $this->getSettingsFromDatabase();
        //$this->backendClass = 'Drupal\phpfastcache\Cache\PhpFastCacheVoidBackend';
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