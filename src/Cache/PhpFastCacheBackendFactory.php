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
    protected $checksumProvider;

    /**
     * The cache tags checksum provider.
     *
     * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
     */
    protected $settings;

    public function __construct($settings, CacheTagsChecksumInterface $checksum_provider)
    {
        //var_dump(func_get_args());exit;
        /**
         * Due to the low-level execution stack of PhpFastCacheBackend
         * we have to hard-include the PhpFastCache autoload here
         */
        require_once __DIR__ . '/../../phpfastcache-php/src/autoload.php';
        $this->backendClass = 'Drupal\phpfastcache\Cache\PhpFastCacheBackend';
        $this->checksumProvider = $checksum_provider;
        $this->cachePool = CacheManager::Files(['ignoreSymfonyNotice' => true]);
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
        return new $this->backendClass($bin, $this->cachePool, $this->checksumProvider);
    }
}