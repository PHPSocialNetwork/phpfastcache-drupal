<?php

namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\phpfastcache\Utils\TokenUtil;

abstract class PhpfastcacheAdminAbstractFields implements PhpfastcacheAdminFieldsInterface {
  public static function getFields(string $driverName, Config $config): array {
    $fields = PhpfastcacheAdminContainerDetailField::getFields(
      $driverName,
      static::getDescription($driverName)
    );

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_tokens" ] = array(
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Token usage'),
    );

    $description = \implode('<br />', TokenUtil::getTokens());

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_tokens" ]['items'] = [
      '#type'        => 'item',
      '#title'       => t('Available tokens'),
      '#markup'      => '',
      '#description' => $description,
    ];

    return $fields;
  }
}