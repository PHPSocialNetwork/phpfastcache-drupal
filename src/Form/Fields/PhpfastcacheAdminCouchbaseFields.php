<?php
namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;

class PhpfastcacheAdminCouchbaseFields implements PhpfastcacheAdminFieldsInterface{

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
      '#title' => t('CouchBase host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description' => t('The CouchBase host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required' => true,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_username" ] = [
      '#type' => 'password',
      '#title' => t('CouchBase username'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.username"),
      '#description' => t('The CouchBase username'),
      '#required' => false,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_password" ] = [
      '#type' => 'password',
      '#title' => t('CouchBase password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description' => t('The CouchBase password, if needed'),
      '#required' => false,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_bucket" ] = [
      '#type' => 'textfield',
      '#title' => t('CouchBase bucket'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.bucket"),
      '#description' => t('The CouchBase bucket name.<br />Default: <strong>default</strong>'),
      '#required' => true,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_bucket_password" ] = [
      '#type' => 'password',
      '#title' => t('CouchBase bucket password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.bucket_password"),
      '#description' => t('The CouchBase bucket password, if needed'),
      '#required' => false,
    ];

    return $fields;
  }
}