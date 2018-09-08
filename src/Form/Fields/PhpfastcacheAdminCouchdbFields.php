<?php

namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

class PhpfastcacheAdminCouchdbFields extends PhpfastcacheAdminAbstractFields {

  public static function getDescription(string $driverName): string {
    return '';
  }

  public static function getFields(string $driverName, Config $config): array {
    $fields = parent::getFields($driverName, $config);

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Couchdb host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description'   => t('The Couchdb host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Couchdb port'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.port"),
      '#description'   => t('The Couchdb port to connect to.<br />Default: <strong>5984</strong>'),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_ssl" ] = [
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.ssl"),
      '#description' => '',
      '#required' => true,
      '#options' => [
        '0' => t('No'),
        '1' => t('Yes'),
      ],
      '#title' => t('Enable SSL'),
      '#type' => 'select',
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_database" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Couchdb Database'),
      '#default_value' => $config->get(
        "phpfastcache_drivers_config.{$driverName}.database"
      ),
      '#description'   => t(
        'The Couchdb database to use.<br />Default: <strong>phpfastcache</strong>'
      ),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_path" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Couchdb path'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.path"),
      '#description'   => t('The Couchdb path<br />Default: <strong>/</strong>'),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_username" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Couchdb username'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.username"),
      '#description'   => t('The Couchdb username'),
      '#required'      => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_password" ] = [
      '#type'          => 'password',
      '#title'         => t('Couchdb password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description'   => t('The Couchdb password, if needed'),
      '#required'      => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_timeout" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Couchdb timeout'),
      '#default_value' => $config->get(
        "phpfastcache_drivers_config.{$driverName}.timeout"
      ),
      '#description'   => t(
        'The Couchdb timeout in seconds.<br />Default: <strong>10</strong>'
      ),
      '#required'      => TRUE,
    ];

    return $fields;
  }

  public static function setConfig(string $driverName, FormStateInterface $form_state, Config $config) {
    $config->set(
      'phpfastcache_drivers_config.' . $driverName,
      [
        'host'            => (string) $form_state->getValue('phpfastcache_drivers_config_couchdb_host'),
        'port'            => (int) $form_state->getValue('phpfastcache_drivers_config_couchdb_port'),
        'ssl'             => (bool) $form_state->getValue('phpfastcache_drivers_config_couchdb_ssl'),
        'database'        => (string) $form_state->getValue('phpfastcache_drivers_config_couchdb_database'),
        'path'            => (string) $form_state->getValue('phpfastcache_drivers_config_couchdb_path'),
        'username'        => (string) $form_state->getValue('phpfastcache_drivers_config_couchdb_username'),
        'password'        => (string) $form_state->getValue('phpfastcache_drivers_config_couchdb_password'),
        'timeout'         => (int) $form_state->getValue('phpfastcache_drivers_config_couchdb_timeout'),
      ]
    );
  }
}