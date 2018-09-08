<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 05/09/2018
 * Time: 22:03
 */

namespace Drupal\phpfastcache\Cache;

use Drupal\phpfastcache\Exceptions\CacheBackendException;
use Drupal\phpfastcache\Utils\StringUtil;
use Drupal\phpfastcache\Utils\TokenUtil;
use Phpfastcache\CacheManager;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class PhpfastcacheInstanceBuilder {

  /**
   * @param $settings
   *
   * @return \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
   * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
   * @throws \Drupal\phpfastcache\Exceptions\CacheBackendException
   */
  public static function buildInstance($settings): ExtendedCacheItemPoolInterface
  {
    $error          = FALSE;
    $defaultOptions = [
      'defaultTtl' => $settings[ 'phpfastcache_default_ttl' ],
    ];

    $driverName    = $settings[ 'phpfastcache_default_driver' ];
    $driversConfig = $settings[ 'phpfastcache_drivers_config' ];
    $configClass   = sprintf('Phpfastcache\\Drivers\\%s\\Config', \ucfirst(\strtolower($driverName)));
    try {
      $instance = CacheManager::getInstance(
        $driverName,
        new $configClass(
          self::prepareConfiguration(
            \array_merge(
              $defaultOptions,
              $driversConfig[ $driverName ]
            )
          )
        )
      );
    } catch (PhpfastcacheDriverCheckException $e) {
      $error    = "The '{$driverName}' driver failed to initialize with the following error: {$e->getMessage()} line {$e->getLine()} in {$e->getFile()}.";
      $instance = CacheManager::getInstance('Devnull');
    } catch (\Throwable $e) {
      $error    = "The '{$driverName}' driver encountered the following error: {$e->getMessage()} line {$e->getLine()} in {$e->getFile()}.";
      $instance = CacheManager::getInstance('Devnull');
    }

    if ($error) {
      throw new CacheBackendException($error, 0, $e ?? NULL);
    }

    return $instance;
  }

  /**
   * @param array $config
   *
   * @return array
   */
  protected static function prepareConfiguration(array $config): array {
    $normalizedArray = [];

    foreach ($config as $key => $value) {
      if(\is_string($value)){
        $value = TokenUtil::parseTokens($value);
      }
      $normalizedArray[ StringUtil::toCamelCase($key) ] = $value;
    }

    return $normalizedArray;
  }
}