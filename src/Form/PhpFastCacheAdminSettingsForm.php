<?php

namespace Drupal\phpfastcache\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use phpFastCache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;

/**
 * Configure phpfastcache settings for this site.
 */
class PhpFastCacheAdminSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phpfastcache_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['phpfastcache.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phpfastcache.settings');
    $cacheSettings = \Drupal\Core\Site\Settings::get('cache');

    if (!isset($cacheSettings[ 'default' ]) || $cacheSettings[ 'default' ] !== 'cache.backend.phpfastcache') {
      drupal_set_message('The cache backend has not been yet configured to use PhpFastCache.',
        'error');
      drupal_set_message('Please read this topic to learn more: https://drupal.org',
        'error');
      $config->set('phpfastcache_enabled', FALSE)
        ->save();

      return parent::buildForm($form, $form_state);
    }

    /***********************
     *
     * General settings
     *
     ***********************/
    $form[ 'general' ] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form[ 'general' ][ 'phpfastcache_enabled' ] = [
      '#default_value' => (int) $config->get('phpfastcache_enabled'),
      '#description' => $this->t('Enable or disable all the PhpFastCache components'),
      '#required' => TRUE,
      '#options' => [
        '0' => t('No'),
        '1' => t('Yes'),
      ],
      '#title' => $this->t('PhpFastCache enabled'),
      '#type' => 'select',
    ];

    /***********************
     *
     * Drivers wrapper
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          'select[name="phpfastcache_enabled"]' => ['value' => '0'],
        ],
      ],
    ];

    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_default_ttl' ] = [
      '#default_value' => (int) $config->get('phpfastcache_default_ttl'),
      '#description' => $this->t('Enable or disable all the PhpFastCache components'),
      '#required' => TRUE,
      '#title' => $this->t('PhpFastCache default <abbr title="Time to live">TTL</abbr>'),
      '#type' => 'textfield',
    ];

    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_htaccess' ] = [
      '#default_value' => (bool) $config->get('phpfastcache_htaccess'),
      '#description' => $this->t('Automatically generate htaccess for files-based drivers such as Files, Sqlite, etc.'),
      '#required' => TRUE,
      '#options' => [
        '0' => t('No'),
        '1' => t('Yes'),
      ],
      '#title' => $this->t('PhpFastCache auto-htaccess generation'),
      '#type' => 'select',
    ];

    $driversOption = [];
    foreach (CacheManager::getStaticSystemDrivers() as $systemDriver) {
      $driversOption[ strtolower($systemDriver) ] = t(ucfirst($systemDriver));
    }
    ksort($driversOption);

    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_default_driver' ] = [
      '#default_value' => (string) $config->get('phpfastcache_default_driver'),
      '#description' => $this->t('Enable or disable all the PhpFastCache components'),
      '#required' => TRUE,
      '#options' => $driversOption,
      '#title' => $this->t('PhpFastCache driver'),
      '#type' => 'select',
    ];

    /***********************
     *
     * Drivers settings wrapper
     *
     ***********************/

    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = [
      '#type' => 'container',
    ];

    /***********************
     *
     * Driver: APC
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getNoFieldBasedFields('apc', $config)
    );

    /***********************
     *
     * Driver: APCU
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getNoFieldBasedFields('apcu', $config)
    );

    /***********************
     *
     * Driver: COUCHBASE
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getCouchBaseBasedFields('couchbase', $config)
    );

    /***********************
     *
     * Driver: DEVNULL
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getDevelopmentBasedFields('devnull', $config,
        'Devnull is a void cache that cache nothing, useful for development. <br />'
        . '<strong>Do not use this settings in a production environment.</strong>')
    );

    /***********************
     *
     * Driver: FILES
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getFilesBasedFields('files', $config)
    );

    /***********************
     *
     * Driver: LEVELDB
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getFilesBasedFields('leveldb', $config)
    );

    /***********************
     *
     * Driver: Memcache
     *
     ***********************/
    $memcacheDesc = 'If you unsure about the different between Memcache and Memcached <a href="http://serverfault.com/a/63399" target="_blank">please read this</a>';
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getMemcacheBasedFields('memcache', $config, $memcacheDesc)
    );

    /***********************
     *
     * Driver: Memcached
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getMemcacheBasedFields('memcached', $config, $memcacheDesc)
    );

    /***********************
     *
     * Driver: MongoDb
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getMongoDbBasedFields('mongodb', $config)
    );

    /***********************
     *
     * Driver: Predis
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getRedisBasedFields('predis', $config,
        'Predis can be used if your php installation does not provide the PHP Redis Extension')
    );

    /***********************
     *
     * Driver: REDIS
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getRedisBasedFields('redis', $config)
    );

    /***********************
     *
     * Driver: SQLITE
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getFilesBasedFields('sqlite', $config)
    );

    /***********************
     *
     * Driver: SSDB
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getSsdbBasedFields('ssdb', $config)
    );

    /***********************
     *
     * Driver: WINCACHE
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getNoFieldBasedFields('wincache', $config)
    );

    /***********************
     *
     * Driver: XCACHE
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getNoFieldBasedFields('xcache', $config)
    );

    /***********************
     *
     * Driver: ZENDDISK
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getNoFieldBasedFields('zenddisk', $config,
        'This driver however requires that your server runs on Zend Server')
    );

    /***********************
     *
     * Driver: ZENDSHM
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
      $this->getNoFieldBasedFields('zendshm', $config,
        'This driver however requires that your server runs on Zend Server')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    try{
      CacheManager::getInstance($form_state->getValue('phpfastcache_default_driver'), ['ignoreSymfonyNotice' => true]);
    }catch(phpFastCacheDriverCheckException $e){
      $form_state->setError($form, 'This driver is not usable at the moment, error code: ' . $e->getMessage());
    }catch (\Exception $e){
      /**
       * @todo Catch others exception here
       */
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('phpfastcache.settings');
    $config->set('phpfastcache_enabled', (bool) $form_state->getValue('phpfastcache_enabled'))
      ->set('phpfastcache_default_ttl', (int) $form_state->getValue('phpfastcache_default_ttl'))
      ->set('phpfastcache_htaccess', (bool) $form_state->getValue('phpfastcache_htaccess'))
      ->set('phpfastcache_default_driver', (string) $form_state->getValue('phpfastcache_default_driver'))
      /*****************
       * Drivers settings
       *****************/
      /**
       * APC
       */
      ->set('phpfastcache_drivers_config.apc', [])
      /**
       * APCU
       */
      ->set('phpfastcache_drivers_config.apcu', [])
      /**
       * Couchbase
       */
      ->set('phpfastcache_drivers_config.couchbase', [
        'host' => (string) $form_state->getValue('phpfastcache_drivers_config_couchbase_host'),
        'username' => (string) $form_state->getValue('phpfastcache_drivers_config_couchbase_username'),
        'password' => (string) $form_state->getValue('phpfastcache_drivers_config_couchbase_password'),
        'bucket' => (string) $form_state->getValue('phpfastcache_drivers_config_couchbase_bucket'),
        'bucket_password' => (string) $form_state->getValue('phpfastcache_drivers_config_couchbase_bucket_password'),
      ])
      /**
       * Files
       */
      ->set('phpfastcache_drivers_config.files', [
        'path' => (string) $form_state->getValue('phpfastcache_drivers_config_files_path'),
        'security_key' => (string) $form_state->getValue('phpfastcache_drivers_config_files_security_key'),
      ])
      /**
       * Leveldb
       */
      ->set('phpfastcache_drivers_config.leveldb', [
        'path' => (string) $form_state->getValue('phpfastcache_drivers_config_leveldb_path'),
        'security_key' => (string) $form_state->getValue('phpfastcache_drivers_config_leveldb_security_key'),
      ])
      /**
       * Memcache
       */
      ->set('phpfastcache_drivers_config.memcache', [
        'host' => (string) $form_state->getValue('phpfastcache_drivers_config_memcache_host'),
        'port' => (int) $form_state->getValue('phpfastcache_drivers_config_memcache_port'),
        'sasl_username' => (string) $form_state->getValue('phpfastcache_drivers_config_memcache_sasl_username'),
        'sasl_password' => (string) $form_state->getValue('phpfastcache_drivers_config_memcache_sasl_password'),
      ])
      /**
       * Memcached
       */
      ->set('phpfastcache_drivers_config.memcached', [
        'host' => (string) $form_state->getValue('phpfastcache_drivers_config_memcached_host'),
        'port' => (int) $form_state->getValue('phpfastcache_drivers_config_memcached_port'),
        'sasl_username' => (string) $form_state->getValue('phpfastcache_drivers_config_memcached_sasl_username'),
        'sasl_password' => (string) $form_state->getValue('phpfastcache_drivers_config_memcached_sasl_password'),
      ])
      /**
       * MongoDb
       */
      ->set('phpfastcache_drivers_config.mongodb', [
        'host' => (string) $form_state->getValue('phpfastcache_drivers_config_mongodb_host'),
        'port' => (int) $form_state->getValue('phpfastcache_drivers_config_mongodb_port'),
        'username' => (string) $form_state->getValue('phpfastcache_drivers_config_mongodb_username'),
        'password' => (string) $form_state->getValue('phpfastcache_drivers_config_mongodb_password'),
        'timeout' => (int) $form_state->getValue('phpfastcache_drivers_config_mongodb_timeout'),
      ])
      /**
       * Predis
       */
      ->set('phpfastcache_drivers_config.predis', [
        'host' => (string) $form_state->getValue('phpfastcache_drivers_config_predis_host'),
        'port' => (int) $form_state->getValue('phpfastcache_drivers_config_predis_port'),
        'password' => (string) $form_state->getValue('phpfastcache_drivers_config_predis_password'),
        'timeout' => (int) $form_state->getValue('phpfastcache_drivers_config_predis_timeout'),
        'dbindex' => (int) $form_state->getValue('phpfastcache_drivers_config_predis_dbindex'),
      ])
      /**
       * Redis
       */
      ->set('phpfastcache_drivers_config.redis', [
        'host' => (string) $form_state->getValue('phpfastcache_drivers_config_redis_host'),
        'port' => (int) $form_state->getValue('phpfastcache_drivers_config_redis_port'),
        'password' => (string) $form_state->getValue('phpfastcache_drivers_config_redis_password'),
        'timeout' => (int) $form_state->getValue('phpfastcache_drivers_config_redis_timeout'),
        'dbindex' => (int) $form_state->getValue('phpfastcache_drivers_config_redis_dbindex'),
      ])
      /**
       * Sqlite
       */
      ->set('phpfastcache_drivers_config.sqlite', [
        'path' => (string) $form_state->getValue('phpfastcache_drivers_config_sqlite_path'),
        'security_key' => (string) $form_state->getValue('phpfastcache_drivers_config_sqlite_security_key'),
      ])
      /**
       * Ssdb
       */
      ->set('phpfastcache_drivers_config.ssdb', [
        'host' => (string) $form_state->getValue('phpfastcache_drivers_config_ssdb_host'),
        'port' => (int) $form_state->getValue('phpfastcache_drivers_config_ssdb_port'),
        'password' => (string) $form_state->getValue('phpfastcache_drivers_config_ssdb_password'),
        'timeout' => (int) $form_state->getValue('phpfastcache_drivers_config_ssdb_timeout'),
      ])
      ->set('phpfastcache_drivers_config.wincache', [])
      ->set('phpfastcache_drivers_config.xcache', [])
      ->set('phpfastcache_drivers_config.zenddisk', [])
      ->set('phpfastcache_drivers_config.zendshm', [])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /******************************
   *
   * FIELDS GETTERS
   *
   *****************************/

  /**
   * @param string $driverName
   * @param string $driverDescription
   * @return mixed
   */
  protected function getContainerDetailField($driverName, $driverDescription = '') {
    return [
      'driver_container_settings__' . $driverName => [
        '#type' => 'details',
        '#title' => $this->t(ucfirst($driverName) . ' settings'),
        '#description' => $driverDescription,
        '#open' => TRUE,
        '#states' => [
          'visible' => [
            'select[name="phpfastcache_default_driver"]' => ['value' => $driverName],
          ],
        ],
      ],
    ];
  }

  /**
   * @param string $driverName
   * @param \Drupal\Core\Config\Config $config
   * @param string $driverDescription
   * @return array
   */
  protected function getCouchBaseBasedFields($driverName, Config $config, $driverDescription = '') {
    $fields = $this->getContainerDetailField($driverName, $driverDescription);

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('CouchBase host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description' => $this->t('The CouchBase host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_username" ] = [
      '#type' => 'password',
      '#title' => $this->t('CouchBase username'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.username"),
      '#description' => $this->t('The CouchBase username'),
      '#required' => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_password" ] = [
      '#type' => 'password',
      '#title' => $this->t('CouchBase password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description' => $this->t('The CouchBase password, if needed'),
      '#required' => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_bucket" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('CouchBase bucket'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.bucket"),
      '#description' => $this->t('The CouchBase bucket name.<br />Default: <strong>default</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_bucket_password" ] = [
      '#type' => 'password',
      '#title' => $this->t('CouchBase bucket password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.bucket_password"),
      '#description' => $this->t('The CouchBase bucket password, if needed'),
      '#required' => FALSE,
    ];

    return $fields;
  }

  /**
   * @param string $driverName
   * @param \Drupal\Core\Config\Config $config
   * @param string $driverDescription
   * @return array
   */
  protected function getDevelopmentBasedFields($driverName, Config $config, $driverDescription = '') {
    return $this->getContainerDetailField($driverName, $driverDescription);
  }

  /**
   * @param string $driverName
   * @param \Drupal\Core\Config\Config $config
   * @param string $driverDescription
   * @return array
   */
  protected function getFilesBasedFields($driverName, Config $config, $driverDescription = '') {
    $fields = $this->getContainerDetailField($driverName, $driverDescription);
    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_path" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache directory'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.path") ?: sys_get_temp_dir(),
      '#description' => $this->t('The writable path where PhpFastCache will write cache files.<br />Default: <strong>' . sys_get_temp_dir() . '</strong> '),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_security_key" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('Security key'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.security_key"),
      '#description' => $this->t('A security key that will identify your website inside the cache directory.<br />Default: <strong>auto</strong> (website hostname)'),
      '#required' => TRUE,
    ];

    return $fields;
  }

  /**
   * @param string $driverName
   * @param \Drupal\Core\Config\Config $config
   * @param string $driverDescription
   * @return array
   */
  protected function getMemcacheBasedFields($driverName, Config $config, $driverDescription = '') {
    $fields = $this->getContainerDetailField($driverName, $driverDescription);

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('Memcache host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description' => $this->t('The Memcache host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('Memcache port'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.port"),
      '#description' => $this->t('The Memcache port to connect to.<br />Default: <strong>112211</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_sasl_username" ] = [
      '#type' => 'password',
      '#title' => $this->t('Memcache SASL username'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.username"),
      '#description' => $this->t('The Memcache SASL username, if needed'),
      '#required' => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_sasl_password" ] = [
      '#type' => 'password',
      '#title' => $this->t('Memcache SASL password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description' => $this->t('The Memcache SASL password, if needed'),
      '#required' => FALSE,
    ];

    return $fields;
  }

  /**
   * @param string $driverName
   * @param \Drupal\Core\Config\Config $config
   * @param string $driverDescription
   * @return array
   */
  protected function getMongoDbBasedFields($driverName, Config $config, $driverDescription = '') {
    $fields = $this->getContainerDetailField($driverName, $driverDescription);

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('MongoDb host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description' => $this->t('The MongoDb host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('MongoDb port'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.port"),
      '#description' => $this->t('The MongoDb port to connect to.<br />Default: <strong>27017</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_username" ] = [
      '#type' => 'password',
      '#title' => $this->t('MongoDb username'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.username"),
      '#description' => $this->t('The MongoDb username, if needed'),
      '#required' => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_password" ] = [
      '#type' => 'password',
      '#title' => $this->t('MongoDb password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description' => $this->t('The MongoDb password, if needed'),
      '#required' => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_timeout" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('MongoDb timeout'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.timeout"),
      '#description' => $this->t('The MongoDb timeout in seconds, set to <strong>0</strong> for unlimited.<br />Default: <strong>2</strong>'),
      '#required' => TRUE,
    ];

    return $fields;
  }

  /**
   * @param string $driverName
   * @param \Drupal\Core\Config\Config $config
   * @param string $driverDescription
   * @return array
   */
  protected function getNoFieldBasedFields($driverName, Config $config, $driverDescription = '') {
    $fields = $this->getContainerDetailField($driverName, '');
    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_item" ] = [
      '#type' => 'item',
      '#title' => t(':driver driver does not needs specific configuration',
        [':driver' => ucfirst($driverName)]),
      '#markup' => '',
      '#description' => $driverDescription ?: '',
    ];

    return $fields;
  }

  /**
   * @param string $driverName
   * @param \Drupal\Core\Config\Config $config
   * @param string $driverDescription
   * @return array
   */
  protected function getRedisBasedFields($driverName, Config $config, $driverDescription = '') {
    $fields = $this->getContainerDetailField($driverName, $driverDescription);

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redis host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description' => $this->t('The Redis host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redis port'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.port"),
      '#description' => $this->t('The Redis port to connect to.<br />Default: <strong>6379</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_password" ] = [
      '#type' => 'password',
      '#title' => $this->t('Redis password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description' => $this->t('The Redis password if needed'),
      '#required' => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_timeout" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redis timeout'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.timeout"),
      '#description' => $this->t('The Redis timeout in seconds, set to <strong>0</strong> for unlimited.<br />Default: <strong>0</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_dbindex" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redis Database index to use'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.dbindex"),
      '#description' => $this->t('The Redis database index to use. Let to <strong>0</strong> by default.<br />Default: <strong>0</strong>'),
      '#required' => FALSE,
    ];

    return $fields;
  }

  /**
   * @param string $driverName
   * @param \Drupal\Core\Config\Config $config
   * @param string $driverDescription
   * @return array
   */
  protected function getSsdbBasedFields($driverName, Config $config, $driverDescription = '') {
    $fields = $this->getContainerDetailField($driverName, $driverDescription);

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_host" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('SSDB host'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.host"),
      '#description' => $this->t('The SSDB host/ip to connect to.<br />Default: <strong>127.0.0.1</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_port" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('SSDB port'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.port"),
      '#description' => $this->t('The SSDB port to connect to.<br />Default: <strong>8888</strong>'),
      '#required' => TRUE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_password" ] = [
      '#type' => 'password',
      '#title' => $this->t('SSDB password'),
      '#default_value' => $config->get("phpfastcache_drivers_config.{$driverName}.password"),
      '#description' => $this->t('The SSDB password, if needed'),
      '#required' => FALSE,
    ];

    $fields[ 'driver_container_settings__' . $driverName ][ "phpfastcache_drivers_config_{$driverName}_timeout" ] = [
      '#type' => 'textfield',
      '#title' => $this->t('SSDB timeout'),
      '#default_value' => (int) $config->get("phpfastcache_drivers_config.{$driverName}.timeout"),
      '#description' => $this->t('The SSDB timeout in seconds, set to <strong>0</strong> for unlimited.<br />Default: <strong>2</strong>.'),
      '#required' => TRUE,
    ];

    return $fields;
  }
}
