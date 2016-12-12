<?php
namespace Drupal\phpfastcache\Cache;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use phpFastCache\CacheManager;
use Drupal\Core\Cache\DatabaseBackend;


/**
 * Class PhpFastCacheService
 */
class PhpFastCacheBackend implements \Drupal\Core\Cache\CacheBackendInterface
{
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
     * The cache tags checksum provider.
     *
     * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
     */
    protected $checksumProvider;

    public function __construct($bin, $cachePool, CacheTagsChecksumInterface $checksum_provider)
    {
        //var_dump(func_get_args());exit;
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
        require_once __DIR__ . '/../../phpfastcache-php/src/autoload.php';
        $this->cachePool = $cachePool;
        $this->bin = $bin;
        $this->checksumProvider = $checksum_provider;
        $this->binPrefix = 'pfc::' . $this->bin . '::';
    }


    /**
     * @inheritDoc
     */
    public function get($cid, $allow_invalid = false)
    {
        $item = $this->cachePool->getItem($this->normalizeCid($cid));

        if($item->isHit())
        {
            $data = $item->get();
            if($data->valid || $allow_invalid)
            {
                return $data;
            }
            else
            {
                return false;
            }
        }
        else if($allow_invalid)
        {
            return $this->getDrupalCacheStdObject();
        }
        else
        {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(&$cids, $allow_invalid = false)
    {
        $cacheObjects = [];
        foreach ($cids as $cid) {
            $item = $this->get($cid, $allow_invalid);
            if($item !== false){
                $cacheObjects[$cid] = $this->get($cid, $allow_invalid);
                unset($cids[$cid]);
            }
        }
        return $cacheObjects;
    }

    /**
     * @inheritDoc
     */
    public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = [])
    {
        $cacheObject = $this->getDrupalCacheStdObject();
        $cacheObject->data = $data;
        $cacheObject->expire = time();
        $cacheObject->expire = $expire;
        $cacheObject->tags = $tags;
        $cacheObject->serialized = false;
        $cacheObject->valid = true;


        $cacheItem = $this->cachePool->getItem($this->normalizeCid($cid));
        $cacheItem->set($cacheObject);
        $cacheItem->setTags($tags);

        if($expire > 1000000000)
        {
            $date = new \DateTime;
            $date->setTimestamp($expire);
            $cacheItem->expiresAt($date);
        }
        else
        {
            $cacheItem->expiresAfter($expire);
        }
        $this->cachePool->save($cacheItem);
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(array $items)
    {
        foreach ($items as $cid => $item) {
            /**
             * Do not Normalize cid here as it
             * will be done in set() method
             */
            $this->set($cid, $item['data'], (isset($item['expire']) ? $item['expire'] : Cache::PERMANENT), (isset($item['tags']) ? $item['tags'] : []));
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($cid)
    {
        $this->cachePool->deleteItem($this->normalizeCid($cid));
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(array $cids)
    {
        $this->cachePool->deleteItems($cids);
    }

    /**
     * @inheritDoc
     */
    public function deleteAll()
    {
        $this->cachePool->clear();
    }

    /**
     * @inheritDoc
     */
    public function invalidate($cid)
    {
        $cacheItem = $this->cachePool->getItem($this->normalizeCid($cid));
        $cacheObject = $cacheItem->get();
        $cacheObject->valid = false;

        $this->cachePool->save($cacheItem);
    }

    /**
     * @inheritDoc
     */
    public function invalidateMultiple(array $cids)
    {
        foreach ($cids as $cid) {
            $this->invalidate($cid);
        }
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

    /**
     * @return \StdClass
     */
    protected function getDrupalCacheStdObject()
    {
        $object = new \StdClass;

        $object->cid = null;
        $object->expire = null;
        $object->created = null;
        $object->tags = null;
        $object->checksum = null;
        $object->data = null;
        $object->serialized = null;
        $object->valid = null;

        return $object;
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
    protected function normalizeCid($cid) {
        // Nothing to do if the ID is a US ASCII string of 255 characters or less.
        $cid_is_ascii = mb_check_encoding($cid, 'ASCII');
        if (strlen($cid) <= 255 && $cid_is_ascii) {
            return $cid;
        }
        // Return a string that uses as much as possible of the original cache ID
        // with the hash appended.
        $hash = Crypt::hashBase64($cid);
        if (!$cid_is_ascii) {
            return $hash;
        }
        return $this->binPrefix . substr($cid, 0, 255 - strlen($hash)) . $hash;
    }
}