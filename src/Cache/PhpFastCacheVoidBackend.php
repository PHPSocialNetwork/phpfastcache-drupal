<?php
/**
 * Z
 */
namespace Drupal\phpfastcache\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use phpFastCache\Cache\ExtendedCacheItemPoolInterface;

/**
 * Class PhpFastCacheService
 */
class PhpFastCacheVoidBackend implements CacheBackendInterface
{
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
     * Constructs a new PhpFastCacheBackend instance.
     *
     * @param $bin string
     *   The name of the cache bin.
     * @param ExtendedCacheItemPoolInterface $cachePool
     * @param array $settings
     */
    public function __construct($bin, $cachePool, $settings)
    {
    }


    /**
     * @inheritDoc
     */
    public function get($cid, $allow_invalid = false)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(&$cids, $allow_invalid = false)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = [])
    {

    }

    /**
     * @inheritDoc
     */
    public function setMultiple(array $items)
    {

    }

    /**
     * @inheritDoc
     */
    public function delete($cid)
    {

    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(array $cids)
    {

    }

    /**
     * @inheritDoc
     */
    public function deleteAll()
    {

    }

    /**
     * @inheritDoc
     */
    public function invalidate($cid)
    {

    }

    /**
     * @inheritDoc
     */
    public function invalidateMultiple(array $cids)
    {
    }

    /**
     * @inheritDoc
     */
    public function invalidateAll()
    {
        throw new UnsupportedMethodException('Method invalidateAll() is currently not supported by PhpFastCache as there no way to list items in cache');
    }

    /**
     * @inheritDoc
     */
    public function garbageCollection()
    {
        /**
         * Does not concerns PhpFastCache
         */
    }

    /**
     * @inheritDoc
     */
    public function removeBin()
    {
        // TODO: Implement removeBin() method.
    }
}