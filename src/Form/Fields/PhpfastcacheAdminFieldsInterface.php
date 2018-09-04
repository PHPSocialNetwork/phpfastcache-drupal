<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 04/09/2018
 * Time: 23:31
 */

namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;

interface PhpfastcacheAdminFieldsInterface {

  /**
   * @param string                     $driverName
   * @param array                      $fields
   * @param \Drupal\Core\Config\Config $config
   *
   * @return array
   */
  public static function getFields(string $driverName, Config $config): array;

  /**
   * @param string $driverName
   *
   * @return array
   */
  public static function getDescription(string $driverName): string;
}