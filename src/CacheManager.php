<?php

namespace Drupal\phpfastcache;

use Phpfastcache\CacheManager as PhpfastcacheCacheManager;

final class CacheManager extends PhpfastcacheCacheManager {

  /**
   * @param bool $FQCNAsKey Describe keys with Full Qualified Class Name
   *
   * @return string[]
   * @throws \Phpfastcache\Exceptions\PhpfastcacheUnsupportedOperationException
   */
  public static function getDriverList(bool $FQCNAsKey = FALSE): array {
    return array_diff(
      parent::getDriverList($FQCNAsKey),
      static::getUnusableDriverList()
    );
  }

  /**
   * @return array
   */
  public static function getUnusableDriverList(): array {
    return [
      'Cookie',
      'Devfalse',
      'Devtrue',
    ];
  }
}