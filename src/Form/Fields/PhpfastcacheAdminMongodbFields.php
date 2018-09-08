<?php
namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

class PhpfastcacheAdminMongodbFields extends PhpfastcacheAdminAbstractFields{

  public static function getDescription(string $driverName):string
  {
    return '';
  }

  public static function getFields(string $driverName, Config $config): array
  {
    $fields = parent::getFields($driverName, $config);

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type' => 'textfield',
      '#title' => t('MongoDb host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description' => t('The MongoDb host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required' => true,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type' => 'textfield',
      '#title' => t('MongoDb port'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.port"),
      '#description' => t('The MongoDb port to connect to.<br />Default: <strong>27017</strong>'),
      '#required' => true,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_username" ] = [
      '#type' => 'password',
      '#title' => t('MongoDb username'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.username"),
      '#description' => t('The MongoDb username, if needed'),
      '#required' => false,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_password" ] = [
      '#type' => 'password',
      '#title' => t('MongoDb password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description' => t('The MongoDb password, if needed'),
      '#required' => false,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_timeout" ] = [
      '#type' => 'textfield',
      '#title' => t('MongoDb timeout'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.timeout"),
      '#description' => t('The MongoDb timeout in seconds, set to <strong>0</strong> for unlimited.<br />Default: <strong>2</strong>'),
      '#required' => true,
    ];

    return $fields;
  }

  public static function setConfig(string $driverName, FormStateInterface $form_state, Config $config) {
    $config->set(
      'phpfastcache_drivers_config.' . $driverName,
      [
        'host' => (string)$form_state->getValue('phpfastcache_drivers_config_mongodb_host'),
        'port' => (int)$form_state->getValue('phpfastcache_drivers_config_mongodb_port'),
        'username' => (string)$form_state->getValue('phpfastcache_drivers_config_mongodb_username'),
        'password' => (string)$form_state->getValue('phpfastcache_drivers_config_mongodb_password'),
        'timeout' => (int)$form_state->getValue('phpfastcache_drivers_config_mongodb_timeout'),
      ]
    );
  }
}