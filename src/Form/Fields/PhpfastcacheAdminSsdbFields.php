<?php
namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;

class PhpfastcacheAdminSsdbFields implements PhpfastcacheAdminFieldsInterface{

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

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type' => 'textfield',
      '#title' => t('SSDB host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description' => t('The SSDB host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required' => true,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type' => 'textfield',
      '#title' => t('SSDB port'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.port"),
      '#description' => t('The SSDB port to connect to.<br />Default: <strong>8888</strong>'),
      '#required' => true,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_password" ] = [
      '#type' => 'password',
      '#title' => t('SSDB password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description' => t('The SSDB password, if needed'),
      '#required' => false,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_timeout" ] = [
      '#type' => 'textfield',
      '#title' => t('SSDB timeout'),
      '#default_value' => (int)$config->get("phpfastcache_drivers_config.{$driverName}.timeout"),
      '#description' => t('The SSDB timeout in seconds, set to <strong>0</strong> for unlimited.<br />Default: <strong>2</strong>.'),
      '#required' => true,
    ];

    return $fields;
  }
}