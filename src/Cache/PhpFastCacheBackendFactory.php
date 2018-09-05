<?php

namespace Drupal\phpfastcache\Cache;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\phpfastcache\Utils\StringUtil;
use Drupal\phpfastcache\Utils\TokenUtil;
use Phpfastcache\CacheManager;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Class PhpFastCacheBackendFactory
 * @todo Uncamelize class name...
 */
class PhpFastCacheBackendFactory implements CacheFactoryInterface {

  const ENV_DEV  = 'dev';

  const ENV_PROD = 'prod';

  /**
   * Cache collector for debug purposes
   *
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
   *
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(Connection $connection) {
    $this->backendClass = PhpFastCacheBackend::class;
    $this->connection   = $connection;
    $this->settings     = $this->getSettingsFromDatabase();
    $this->cachePool    = $this->getPhpFastCacheInstance();

    if (!$this->settings[ 'phpfastcache_enabled' ]) {

      /**
       * @todo Use route identifier
       */
      if (strpos($_SERVER[ 'REQUEST_URI' ], 'admin/config/development/phpfastcache') === FALSE) {
        /**
         * At this level nothing is efficient
         * - drupal_set_message() is not working/displaying anything
         * - throwing exception displays a fatal error without backtrace
         * - echoing destroys header leading to another fatal error
         *
         * Let's dying miserably by showing a simple but efficient message
         */
        if ($this->settings[ 'phpfastcache_env' ] === self::ENV_DEV) {
          \Drupal::messenger()->addWarning(
            'PhpFastCache is not enabled, please go to <strong>admin/config/development/phpfastcache</strong> then configure PhpFastCache or comment out the cache backend override in settings.php.'
          );
        }
        else {
          \Drupal::messenger()->addError(
            'PhpFastCache is not enabled, please go to <strong>admin/config/development/phpfastcache</strong> then configure PhpFastCache or comment out the cache backend override in settings.php.'
          );
        }
        $this->backendClass = PhpFastCacheVoidBackend::class;
      }
    }
  }

  /**
   * @param $settings
   *
   * @return \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
   */
  protected function getPhpFastCacheInstance(): ExtendedCacheItemPoolInterface {
    return PhpfastcacheInstanceBuilder::buildInstance($this->settings);
  }

  /**
   * Get settings from database.
   * At this level of runtime execution
   * settings are not available yet.
   *
   * @return array
   */
  protected function getSettingsFromDatabase(): array {
    $query  = 'SELECT `data`
                  FROM {' . $this->connection->escapeTable('config') . '} 
                  WHERE `name` = :name
                  LIMIT 1';
    $params = [':name' => 'phpfastcache.settings'];
    $result = $this->connection->query($query, $params);

    return (array) unserialize($result->fetchField(), ['allowed_classes' => FALSE]);
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
    if (\in_array($bin, $this->settings[ 'phpfastcache_bins' ], TRUE) || \in_array('default', $this->settings[ 'phpfastcache_bins' ], TRUE)) {
      return new $this->backendClass($bin, $this->cachePool, $this->settings);
    }
    else {
      return new PhpFastCacheVoidBackend($bin, $this->cachePool, $this->settings);
    }
  }
}
