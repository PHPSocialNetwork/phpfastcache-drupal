<?php
namespace Drupal\phpfastcache\Form\Fields;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

class PhpfastcacheAdminFilesFields extends PhpfastcacheAdminAbstractFields{

  public static function getDescription(string $driverName):string
  {
    return '';
  }

  public static function getFields(string $driverName, Config $config): array
  {
    $fields = parent::getFields($driverName, $config);

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

    if($driverName === 'files'){
      $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_secure_file_manipulation" ] = [
        '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.secure_file_manipulation"),
        '#description' => t('A secure mode that will write file in a secured mode (writing on temporary name then rename).'),
        '#required' => true,
        '#options' => [
          '0' => t('No'),
          '1' => t('Yes'),
        ],
        '#title' => t('Secure file manipulation'),
        '#type' => 'select',
      ];

      $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_cache_file_extension" ] = [
        '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.cache_file_extension"),
        '#description' => t('Allows you to set a custom cache file extension.'),
        '#required' => true,
        '#options' => [
          'txt' => 'txt',
          'pfc' => 'pfc',
          'cache' => 'cache',
          'db' => 'db',
        ],
        '#title' => t('Cache file extension'),
        '#type' => 'select',
      ];
    }

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_htaccess" ] = [
      '#default_value' => (bool)$config->get('phpfastcache_htaccess'),
      '#description' => t('Automatically generate htaccess for files-based drivers such as Files, Sqlite and Leveldb.'),
      '#required' => true,
      '#options' => [
        '0' => t('No'),
        '1' => t('Yes'),
      ],
      '#title' => t('Auto-htaccess generation'),
      '#type' => 'select',
    ];

    return $fields;
  }

  public static function setConfig(string $driverName, FormStateInterface $form_state, Config $config) {
    $configArray  =       [
      'path' => (string)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_path"),
      'security_key' => (string)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_security_key"),
      'htaccess' => (bool)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_htaccess"),
    ];

    if($driverName === 'files'){
      $configArray['secure_file_manipulation'] = (bool)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_secure_file_manipulation");
      $configArray['cache_file_extension'] = (string)$form_state->getValue("phpfastcache_drivers_config_{$driverName}_cache_file_extension");
    }

    $config->set('phpfastcache_drivers_config.' . $driverName, $configArray);
  }
}