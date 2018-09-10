<?php

namespace Drupal\phpfastcache\Cache;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\phpfastcache\Exceptions\CacheBackendException;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;

/**
 * Class PhpFastCacheBackendFactory
 *
 */
class PhpfastcacheBackendFactory implements CacheFactoryInterface {

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
   * @var \Drupal\Core\Cache\CacheBackendInterface[]
   */
  protected $cacheBins = [];

  /**
   * PhpFastCacheBackendFactory constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
   * @throws \Drupal\phpfastcache\Exceptions\CacheBackendException
   */
  public function __construct(Connection $connection) {
    $this->backendClass = PhpfastcacheBackend::class;
    $this->connection   = $connection;
    $this->settings     = $this->getSettingsFromDatabase();
    $this->cachePool    = $this->getPhpfastcacheInstance();

    if (!$this->settings[ 'phpfastcache_enabled' ] && \Drupal::routeMatch()->getRouteName() !== 'phpfastcache.admin_settings_form') {
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
      $this->backendClass = PhpfastcacheVoidBackend::class;
    }
  }

  /**
   * @return \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
   * @throws \Drupal\phpfastcache\Exceptions\CacheBackendException
   */
  protected function getPhpfastcacheInstance(): ExtendedCacheItemPoolInterface {
    return PhpfastcacheInstanceBuilder::buildInstance($this->settings);
  }

  /**
   * Get settings straight from database.
   * At this level of runtime execution,
   * settings API is not yet available.
   *
   * @return array
   */
  public function getSettingsFromDatabase(): array {
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
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend object for the specified cache bin.
   * @throws CacheBackendException
   */
  public function get($bin) {
    try {
      if (!isset($this->cacheBins[ $bin ])) {
        $this->cacheBins[ $bin ] = new $this->backendClass($bin, $this->cachePool, $this->settings);
      }
      return $this->cacheBins[ $bin ];
    } catch (\Throwable $e) {
      throw new CacheBackendException(
        \sprintf(
          'Failed to create a cache backend instance for "%s" cache bin, got the following error: %s',
          $bin,
          $e->getMessage()
        )
      );
    }
  }
}
