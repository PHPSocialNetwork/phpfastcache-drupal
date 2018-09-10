<?php
namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

class PhpfastcacheAdminMemcacheFields implements PhpfastcacheAdminFieldsInterface{

  public static function getDescription(string $driverName):string
  {
    return 'If you unsure about the different between Memcache and Memcached <a href="http://serverfault.com/a/63399" target="_blank">please read this</a>';
  }

  public static function getFields(string $driverName, Config $config): array
  {
    $fields = PhpfastcacheAdminContainerDetailField::getFields(
      $driverName,
      self::getDescription($driverName)
    );


    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type' => 'textfield',
      '#title' => t('Memcache host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description' => t('The Memcache host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required' => true,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type' => 'textfield',
      '#title' => t('Memcache port'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.port"),
      '#description' => t('The Memcache port to connect to.<br />Default: <strong>112211</strong>'),
      '#required' => true,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_sasl_user" ] = [
      '#type' => 'password',
      '#title' => t('Memcache SASL user'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.user"),
      '#description' => t('The Memcache SASL username, if needed'),
      '#required' => false,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_sasl_password" ] = [
      '#type' => 'password',
      '#title' => t('Memcache SASL password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description' => t('The Memcache SASL password, if needed'),
      '#required' => false,
    ];

    return $fields;
  }

  public static function setConfig(string $driverName, FormStateInterface $form_state, Config $config) {
    $config->set(
      'phpfastcache_drivers_config.' . $driverName,
      [
        'host' => (string)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_host"),
        'port' => (int) $form_state->getValue("phpfastcache_drivers_config_{$driverName}_port"),
        'sasl_user' => (string)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_sasl_user"),
        'sasl_password' => (string)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_sasl_password"),
      ]
    );
  }
}