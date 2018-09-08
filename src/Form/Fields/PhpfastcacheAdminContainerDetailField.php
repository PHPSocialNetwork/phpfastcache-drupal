<?php
namespace Drupal\phpfastcache\Form\Fields;

class PhpfastcacheAdminContainerDetailField{
  public static function getFields(string $driverName, string $driverDescription): array
  {
    return [
      'driver_container_settings__' . $driverName => [
        '#type' => 'details',
        '#title' => t(ucfirst($driverName) . ' settings'),
        '#description' => $driverDescription,
        '#open' => true,
        '#states' => [
          'visible' => [
            'select[name="phpfastcache_default_driver"]' => ['value' => $driverName],
          ],
        ],
      ],
    ];
  }
}