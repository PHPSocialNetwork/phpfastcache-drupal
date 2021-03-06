<?php

namespace Drupal\phpfastcache\Cache;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\DatabaseBackend;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;


/**
 * Class PhpFastCacheService
 *
 * @todo Uncamelize class name...
 */
class PhpfastcacheBackend implements CacheBackendInterface {

  /**
   * @var ExtendedCacheItemPoolInterface
   */
  protected $cachePool;

  /**
   * The name of the cache bin to use.
   *
   * @var string
   */
  protected $bin;

  /**
   * Prefix for all keys in this cache bin.
   *
   * Includes the site-specific prefix in $sitePrefix.
   *
   * @var string
   */
  protected $binPrefix;

  /**
   * @var array
   */
  protected $settings;

  /**
   * Constructs a new PhpFastCacheBackend instance.
   *
   * @param                                $bin string
   *                                            The name of the cache bin.
   * @param ExtendedCacheItemPoolInterface $cachePool
   * @param array                          $settings
   */
  public function __construct(string $bin, ExtendedCacheItemPoolInterface $cachePool, array $settings) {
    $this->cachePool = $cachePool;
    $this->bin       = $bin;
    $this->binPrefix = 'pfc.' . $this->bin . '.';
    $this->settings  = $settings;
  }


  /**
   * @inheritDoc
   */
  public function get($cid, $allow_invalid = FALSE) {
    $item = $this->cachePool->getItem($this->normalizeCid($cid));

    if ($item->isHit()) {
      $data = $item->get();
      if (($data && $data->valid) || $allow_invalid) {
        return $data;
      }
      return FALSE;
    }

    if ($allow_invalid) {
      return FALSE;
    }

    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $cacheObjects = [];
    foreach ($cids as $cid) {
      $item = $this->get($cid, $allow_invalid);
      if ($item !== FALSE) {
        $cacheObjects[ $cid ] = $item;
        unset($cids[ $cid ]);
      }
    }

    return $cacheObjects;
  }

  /**
   * @inheritDoc
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    $cacheObject             = $this->getDrupalCacheStdObject();
    $cacheObject->cid        = $cid;
    $cacheObject->data       = $data;
    $cacheObject->expire     = $expire;
    $cacheObject->tags       = $tags;
    $cacheObject->serialized = FALSE;
    $cacheObject->valid      = TRUE;


    $cacheItem = $this->cachePool->getItem($this->normalizeCid($cid));
    $cacheItem->set($cacheObject);
    // Not sure if its used atm
    // $cacheItem->setTags(array_map([$this, 'normalizeCid'], $tags));

    if ($expire > 1000000000) {
      $date = new \DateTime;
      $date->setTimestamp($expire);
      $cacheItem->expiresAt($date);
    }
    else {
      $cacheItem->expiresAfter(((int) $expire === Cache::PERMANENT ? 60 * 60 * 24 * 365 : $expire));
    }
    $this->cachePool->save($cacheItem);
  }

  /**
   * @inheritDoc
   */
  public function setMultiple(array $items) {
    foreach ($items as $cid => $item) {
      /**
       * Do not Normalize cid here as it
       * will be done in set() method
       */
      $this->set($cid, $item[ 'data' ], $item[ 'expire' ] ?? Cache::PERMANENT, $item[ 'tags' ] ?? []);
    }
  }

  /**
   * @inheritDoc
   */
  public function delete($cid) {
    $this->cachePool->deleteItem($this->normalizeCid($cid));
  }

  /**
   * @inheritDoc
   */
  public function deleteMultiple(array $cids) {
    $this->cachePool->deleteItems(\array_map([$this, 'normalizeCid'], $cids));
  }

  /**
   * @inheritDoc
   */
  public function deleteAll() {
    $this->cachePool->clear();
  }

  /**
   * @inheritDoc
   */
  public function invalidate($cid) {
    $cacheItem   = $this->cachePool->getItem($this->normalizeCid($cid));
    $cacheObject = $cacheItem->get();

    if (\is_object($cacheObject)) {
      $cacheObject->valid = FALSE;
    }

    $this->cachePool->save($cacheItem);
  }

  /**
   * @inheritDoc
   */
  public function invalidateMultiple(array $cids) {
    foreach ($cids as $cid) {
      $this->invalidate($cid);
    }
  }

  /**
   * @inheritDoc
   */
  public function invalidateAll() {
    $this->cachePool->clear();
    //throw new UnsupportedMethodException('Method invalidateAll() is currently not supported by PhpFastCache as there no way to list items in cache');
  }

  /**
   * @inheritDoc
   */
  public function garbageCollection() {
    /**
     * Does not concerns PhpFastCache
     */
  }

  /**
   * @inheritDoc
   */
  public function removeBin() {
    // TODO: Implement removeBin() method.
  }

  /**
   * @return PhpfastcacheStoredObject
   */
  protected function getDrupalCacheStdObject(): PhpfastcacheStoredObject {
    return new PhpfastcacheStoredObject();
  }

  /**
   * Borrowed from DatabaseBackend cache backend
   * Normalizes a cache ID in order to comply with database limitations.
   *
   * @param string $cid
   *   The passed in cache ID.
   *
   * @return string
   *   An ASCII-encoded cache ID that is at most 255 characters long.
   * @see DatabaseBackend::normalizeCid()
   */
  protected function normalizeCid(string $cid): string {
    static $maxKeyLength = 64;

    /**
     * Add Bin Prefix
     */
    $cid = $this->binPrefix . $cid;

    /**
     * Add Phpfastcache Prefix
     */
    $cid = ($this->settings[ 'phpfastcache_prefix' ] ?: 'D8') . '.' . $cid;

    /**
     * Nothing to do if the ID is a US ASCII string of 64 characters or less.
     */
    $cid_is_ascii = mb_check_encoding($cid, 'ASCII');
    if (\strlen($cid) <= $maxKeyLength && $cid_is_ascii) {
      return $this->replaceUnsupportedPsr6Characters($cid);
    }

    /**
     * Return a string that uses as much as possible of the original cache ID
     * with the hash appended.
     */
    $hash = Crypt::hashBase64($cid);

    $hash = '_._' . trim(substr($hash, 3), '-_.');

    if (!$cid_is_ascii) {
      return $this->replaceUnsupportedPsr6Characters($hash);
    }

    return $this->replaceUnsupportedPsr6Characters(
      substr($cid, 0, $maxKeyLength - \strlen($hash)) . $hash
    );
  }

  /**
   * @param string $str
   *
   * @return string
   */
  protected function replaceUnsupportedPsr6Characters(string $str): string {
    return str_replace(
      ['{', '}', '(', ')', '/', '\\', '@', ':'],
      '_',
      $str
    );
  }
}
