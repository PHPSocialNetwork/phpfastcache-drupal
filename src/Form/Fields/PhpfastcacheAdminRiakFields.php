<?php

namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

class PhpfastcacheAdminRiakFields extends PhpfastcacheAdminAbstractFields {

  public static function getDescription(string $driverName): string {
    return '';
  }

  public static function getFields(string $driverName, Config $config): array {
    $fields = parent::getFields($driverName, $config);

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Riak host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description'   => t('The Riak host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Riak port'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.port"),
      '#description'   => t('The Riak port to connect to.<br />Default: <strong>8098</strong>'),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_prefix" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Riak prefix'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.prefix"),
      '#description'   => t('The Riak prefix.<br />Default: <strong>riak</strong>'),
      '#required'      => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_bucket_name" ] = [
      '#type'          => 'textfield',
      '#title'         => t('Riak bucket'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.bucket_name"),
      '#description'   => t('The Riak bucket name.<br />Default: <strong>phpfastcache</strong>'),
      '#required'      => TRUE,
    ];

    return $fields;
  }

  public static function setConfig(string $driverName, FormStateInterface $form_state, Config $config) {
    $config->set(
      'phpfastcache_drivers_config.' . $driverName,
      [
        'host'            => (string) $form_state->getValue('phpfastcache_drivers_config_riak_host'),
        'port'            => (int) $form_state->getValue('phpfastcache_drivers_config_riak_port'),
        'prefix'          => (string) $form_state->getValue('phpfastcache_drivers_config_riak_prefix'),
        'bucket_name'     => (string) $form_state->getValue('phpfastcache_drivers_config_riak_bucket_name'),
      ]
    );
  }
}