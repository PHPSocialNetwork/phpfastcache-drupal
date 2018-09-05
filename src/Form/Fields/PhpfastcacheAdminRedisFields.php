<?php

namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

class PhpfastcacheAdminRedisFields implements PhpfastcacheAdminFieldsInterface {

  /**
   * @param string $driverName
   *
   * @return string
   */
  public static function getDescription(string $driverName): string {
    switch ($driverName) {
      case 'predis':
        return 'Predis can be used if your php installation does not provide the PHP Redis Extension.<br />
        Run the following command to require the Predis binaries: <code>$ composer require predis/predis</code>';
        break;

      case 'redis':
        return 'Redis requires that the php "redis" extension to be installed and enabled..<br />
        Run the following command to install the redis extension: <code>$ sudo apt-get install php-redis</code>';
        break;
      default:
        return '';
        break;
    }
  }

  public static function getFields(string $driverName, Config $config): array {
    $fields = PhpfastcacheAdminContainerDetailField::getFields(
      $driverName,
      self::getDescription($driverName)
    );

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Redis host'),
      '#default_value' => $config->get(
        "phpfastcache_drivers_config.{$driverName}.host"
      ),
      '#description'   => t(
        'The Redis host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'
      ),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Redis port'),
      '#default_value' => $config->get(
        "phpfastcache_drivers_config.{$driverName}.port"
      ),
      '#description'   => t(
        'The Redis port to connect to.<br />Default: <strong>6379</strong>'
      ),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_password" ] = [
      '#type'          => 'password',
      '#title'         => t('Redis password'),
      '#default_value' => $config->get(
        "phpfastcache_drivers_config.{$driverName}.password"
      ),
      '#description'   => t('The Redis password if needed'),
      '#required'      => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_timeout" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Redis timeout'),
      '#default_value' => $config->get(
        "phpfastcache_drivers_config.{$driverName}.timeout"
      ),
      '#description'   => t(
        'The Redis timeout in seconds, set to <strong>0</strong> for unlimited.<br />Default: <strong>0</strong>'
      ),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_database" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Redis Database index to use'),
      '#default_value' => $config->get(
        "phpfastcache_drivers_config.{$driverName}.database"
      ),
      '#description'   => t(
        'The Redis database index to use. Let to <strong>0</strong> by default.<br />Default: <strong>0</strong>'
      ),
      '#required'      => FALSE,
    ];

    return $fields;
  }

  public static function setConfig(string $driverName, FormStateInterface $form_state, Config $config) {
    $config->set(
      'phpfastcache_drivers_config.' . $driverName,
      [
        'host' => (string)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_host"),
        'port' => (int)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_port"),
        'password' => (string)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_password"),
        'timeout' => (int)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_timeout"),
        'database' => (int)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_database"),
      ]
    );
  }
}