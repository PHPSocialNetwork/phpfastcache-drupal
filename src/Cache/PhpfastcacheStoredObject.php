<?php

namespace Drupal\phpfastcache\Cache;


/**
 * Class PhpfastcacheStoredObject
 */
class PhpfastcacheStoredObject extends \StdClass {

  public $cid;

  public $expire;

  public $created;

  public $tags;

  public $checksum;

  public $data;

  public $serialized;

  public $valid;
}
