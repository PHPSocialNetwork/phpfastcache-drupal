<?php
namespace Drupal\phpfastcache\Form\Fields;

class PhpfastcacheAdminFieldClassMap{

  /**
   * @param string $driverName
   *
   * @return PhpfastcacheAdminFieldsInterface The class name as string
   */
  public static function getClass(string $driverName): string
  {
    switch($driverName)
    {
      case 'apc':
      case 'apcu':
      case 'devnull':
      case 'wincache':
      case 'xcache':
      case 'zenddisk':
      case 'zendshm':
          return PhpfastcacheAdminNoFieldFields::class;
        break;
      case 'couchbase':
        return PhpfastcacheAdminCouchbaseFields::class;
        break;
      case 'couchdb':
        return PhpfastcacheAdminCouchdbFields::class;
        break;
      case 'files':
      case 'leveldb':
      case 'sqlite':
        return PhpfastcacheAdminFilesFields::class;
        break;
      case 'memcache':
      case 'memcached':
        return PhpfastcacheAdminMemcacheFields::class;
        break;
      case 'mongodb':
        return PhpfastcacheAdminMongodbFields::class;
        break;
      case 'predis':
      case 'redis':
        return PhpfastcacheAdminRedisFields::class;
        break;
      case 'riak':
        return PhpfastcacheAdminRiakFields::class;
        break;
      case 'ssdb':
        return PhpfastcacheAdminSsdbFields::class;
        break;
      default:
        return PhpfastcacheAdminUnknownFieldFields::class;
        break;
    }
  }
}