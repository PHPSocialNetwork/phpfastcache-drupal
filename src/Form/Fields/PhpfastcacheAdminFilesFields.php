<?php
namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;

class PhpfastcacheAdminFilesFields implements PhpfastcacheAdminFieldsInterface{

  public static function getDescription(string $driverName):string
  {
    return '';
  }

  public static function getFields(string $driverName, Config $config): array
  {
    $fields = PhpfastcacheAdminContainerDetailField::getFields(
      $driverName,
      self::getDescription($driverName)
    );

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_item" ] = [
      '#type' => 'item',
      '#title' => '',
      '#markup' => '<strong>Warning:</strong> Files-based drivers requires an highly performance I/O server (SSD or better), else the site performances will get worse than expected.',
      '#description' => '',
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_path" ] = [
      '#type' => 'textfield',
      '#title' => t('Cache directory'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.path") ?: sys_get_temp_dir(),
      '#description' => t('The writable path where PhpFastCache will write cache files.<br />Default: <strong>' . sys_get_temp_dir() . '</strong> '),
      '#required' => true,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_security_key" ] = [
      '#type' => 'textfield',
      '#title' => t('Security key'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.security_key"),
      '#description' => t('A security key that will identify your website inside the cache directory.<br />Default: <strong>auto</strong> (website hostname)'),
      '#required' => true,
    ];

    return $fields;
  }
}