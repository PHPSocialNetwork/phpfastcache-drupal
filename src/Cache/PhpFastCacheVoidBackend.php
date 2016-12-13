<?php
/**
 * Z
 */
namespace Drupal\phpfastcache\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsChecksumInterface;


/**
 * Class PhpFastCacheService
 */
class PhpFastCacheVoidBackend implements \Drupal\Core\Cache\CacheBackendInterface
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

    public function __construct($bin, $cachePool)
    {
        /**
         * Constructs a new ApcuBackend instance.
         *
         * @param string $bin
         *   The name of the cache bin.
         * @param string $site_prefix
         *   The prefix to use for all keys in the storage that belong to this site.
         * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
         *   The cache tags checksum provider.
         */
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