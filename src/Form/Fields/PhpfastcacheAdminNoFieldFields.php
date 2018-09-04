<?php

namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;

class PhpfastcacheAdminNoFieldFields implements PhpfastcacheAdminFieldsInterface {

  /**
   * @param string $driverName
   *
   * @return string
   */
  public static function getDescription(string $driverName): string {
    switch ($driverName) {
      case 'devnull':
        return 'Devnull is a void cache that cache nothing, useful for development. <br />'
               . '<strong>Do not use this settings in a production environment.</strong>';
        break;
      case 'zenddisk':
      case 'zendshm':
        return 'This driver however requires that your server runs on Zend Server';
        break;
      default:
        return '';
        break;
    }
  }

  /**
   * @param string                     $driverName
   * @param \Drupal\Core\Config\Config $config
   *
   * @return array
   */
  public static function getFields(string $driverName, Config $config): array {
    $fields = PhpfastcacheAdminContainerDetailField::getFields(
      $driverName,
      self::getDescription($driverName)
    );

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_item" ] = [
      '#type'        => 'item',
      '#title'       => t(
        ':driver driver does not needs specific configuration',
        [':driver' => ucfirst($driverName)]
      ),
      '#markup'      => '',
      '#description' => self::getDescription($driverName) ?: '',
    ];

    return $fields;
  }
}