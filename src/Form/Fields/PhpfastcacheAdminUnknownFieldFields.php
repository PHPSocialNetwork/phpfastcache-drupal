<?php

namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

class PhpfastcacheAdminUnknownFieldFields implements PhpfastcacheAdminFieldsInterface {

  /**
   * @param string $driverName
   *
   * @return string
   */
  public static function getDescription(string $driverName): string {
    return '';
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
        'Unknown driver ":driver". It is may not yet supported by the Phpfastcache module',
        [':driver' => ucfirst($driverName)]
      ),
      '#markup'      => '',
      '#description' => self::getDescription($driverName) ?: '',
    ];

    return $fields;
  }

  public static function setConfig(string $driverName, FormStateInterface $form_state, Config $config) {}
}