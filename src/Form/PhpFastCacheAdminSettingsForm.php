<?php

namespace Drupal\phpfastcache\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\phpfastcache\Cache\PhpFastCacheBackendFactory;
use Drupal\phpfastcache\Form\Fields\PhpfastcacheAdminCouchbaseFields;
use Drupal\phpfastcache\Form\Fields\PhpfastcacheAdminFilesFields;
use Drupal\phpfastcache\Form\Fields\PhpfastcacheAdminMemcacheFields;
use Drupal\phpfastcache\Form\Fields\PhpfastcacheAdminMongodbFields;
use Drupal\phpfastcache\Form\Fields\PhpfastcacheAdminNoFieldFields;
use Drupal\phpfastcache\Form\Fields\PhpfastcacheAdminRedisFields;
use Drupal\phpfastcache\Form\Fields\PhpfastcacheAdminSsdbFields;
use Phpfastcache\CacheManager;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;

/**
 * Configure phpfastcache settings for this site.
 */
class PhpFastCacheAdminSettingsForm extends ConfigFormBase
{
    const PREFIX_REGEXP = '^\w*$';
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'phpfastcache_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['phpfastcache.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $randomService = new Random();
        $config = $this->config('phpfastcache.settings');

        if(!phpfastcache_is_library_installed())
        {
          \Drupal::messenger()->addError('The Phpfastcache library is not installed.');
          $config->set('phpfastcache_enabled', false)
                 ->save();

          return parent::buildForm($form, $form_state);
        }

        if (!phpfastcache_is_settings_php_configured()) {
          \Drupal::messenger()->addError('The cache backend has not been yet configured to use PhpFastCache.');
          \Drupal::messenger()->addError('Please read this topic to learn more: https://api.drupal.org/api/drupal/core!core.api.php/group/cache/8.2.x');
          $config->set('phpfastcache_enabled', false)
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
          '#open' => true,
        ];

        $form[ 'general' ][ 'phpfastcache_enabled' ] = [
          '#default_value' => (int)$config->get('phpfastcache_enabled'),
          '#description' => $this->t('Enable or disable all the PhpFastCache components'),
          '#required' => true,
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

        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_env' ] = [
          '#default_value' => (string)$config->get('phpfastcache_env'),
          '#description' => $this->t('<strong>Production</strong>: Will displays minimal information in case of failure.<br />
                                    <strong>Development</strong>: Will displays very verbose information in case of failure.'),
          '#required' => true,
          '#options' => [
            PhpFastCacheBackendFactory::ENV_DEV => t('Production'),
            PhpFastCacheBackendFactory::ENV_PROD => t('Development'),
          ],
          '#title' => $this->t('PhpFastCache environment'),
          '#type' => 'select',
        ];

        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_prefix' ] = [
          '#default_value' => (string) ($config->get('phpfastcache_prefix') ?: $randomService->name(6, true)),
          '#description' => $this->t('The cache keyspace prefix that will be used to identify this website. 
                                    This value length <strong>MUST</strong> be up to 8 chars and 4 chars minimum. <br />
                                    This value <strong>MUST</strong> be unique depending your other Drupal installations on this cache backend. <br />
                                    This value <strong>MUST</strong> be alpha-numeric (' . self::PREFIX_REGEXP . ')'),
          '#required' => true,
          '#title' => $this->t('Cache keyspace prefix'),
          '#type' => 'textfield',
        ];

        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_default_ttl' ] = [
          '#default_value' => (int)$config->get('phpfastcache_default_ttl'),
          '#description' => $this->t('Enable or disable all the PhpFastCache components'),
          '#required' => true,
          '#title' => $this->t('Default <abbr title="Time to live">TTL</abbr>'),
          '#type' => 'textfield',
        ];


        $binDescCallback = function ($binName, $binDesc = '') {
            return '<span>' . t(ucfirst($binName)) . '</span>' . ($binDesc ? '&nbsp;-&nbsp;<small>' . t($binDesc) . '</small>' : '');
        };

        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_bins' ] = [
          '#default_value' => (array)$config->get('phpfastcache_bins'),
          '#description' => 'See /core/core.services.yml for more information about bin uses',
          '#required' => false,
          '#options' => [
            'default' => $binDescCallback('default', 'Default bin if not specified by modules/core or for any custom/unknown bins. <strong>Recommended</strong>'),
            'menu' => $binDescCallback('menu', 'Menu tree/items'),
            'bootstrap' => $binDescCallback('bootstrap', 'Drupal bootstrap/core initialization'),
            'render' => $binDescCallback('render',
              'You must expect the cache size to grow up quickly, make sure that the driver you choose have enough memory/disk space.'),
            'config' => $binDescCallback('config', 'You will have to purge the cache after each settings changes'),
            'dynamic_page_cache' => $binDescCallback('dynamic page cache', ''),
            'entity' => $binDescCallback('entity', 'You will have to purge the cache after each entity changes'),
            'discovery' => $binDescCallback('discovery', 'Used for plugin manager, entity type manager, field manager, etc.'),
          ],
          '#title' => $this->t('Bins handled by PhpFastCache'),
          '#type' => 'checkboxes',
        ];

        $driversOption = [];
        foreach (CacheManager::getDriverList() as $systemDriver) {
            if($this->isAvailableDriver($systemDriver)){
              $driversOption[ strtolower($systemDriver) ] = t(ucfirst($systemDriver));
            }else{
              $driversOption[ strtolower($systemDriver) ] = t(ucfirst($systemDriver)) . ' [UNAVAILABLE]';
            }
        }
        ksort($driversOption);

        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_default_driver' ] = [
          '#default_value' => (string)$config->get('phpfastcache_default_driver'),
          '#description' => $this->t('Enable or disable all the PhpFastCache components.'),
          '#required' => true,
          '#options' => $driversOption,
          '#title' => $this->t('Cache driver'),
          '#type' => 'select',
        ];

        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_htaccess' ] = [
          '#default_value' => (bool)$config->get('phpfastcache_htaccess'),
          '#description' => $this->t('Automatically generate htaccess for files-based drivers such as Files, Sqlite and Leveldb.'),
          '#required' => true,
          '#options' => [
            '0' => t('No'),
            '1' => t('Yes'),
          ],
          '#title' => $this->t('Auto-htaccess generation'),
          '#type' => 'select',
          '#states' => [
            'visible' => [
              'select[name="phpfastcache_default_driver"]' => [
                ['value' => 'files'],
                ['value' => 'sqlite'],
                ['value' => 'leveldb'],
              ],
            ],
          ],
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
          PhpfastcacheAdminNoFieldFields::getFields('apc', $config)
        );

        /***********************
         *
         * Driver: APCU
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminNoFieldFields::getFields('apcu', $config)
        );

        /***********************
         *
         * Driver: COUCHBASE
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminCouchbaseFields::getFields('couchbase', $config)
        );

        /***********************
         *
         * Driver: DEVNULL
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminNoFieldFields::getFields('devnull', $config)
        );

        /***********************
         *
         * Driver: FILES
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminFilesFields::getFields('files', $config)
        );

        /***********************
         *
         * Driver: LEVELDB
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminFilesFields::getFields('leveldb', $config)
        );

        /***********************
         *
         * Driver: Memcache
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminMemcacheFields::getFields('memcache', $config)
        );

        /***********************
         *
         * Driver: Memcached
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminMemcacheFields::getFields('memcached', $config)
        );

        /***********************
         *
         * Driver: MongoDb
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminMongodbFields::getFields('mongodb', $config)
        );

        /***********************
         *
         * Driver: Predis
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminRedisFields::getFields('predis', $config)
        );

        /***********************
         *
         * Driver: REDIS
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminRedisFields::getFields('redis', $config)
        );

        /***********************
         *
         * Driver: SQLITE
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminFilesFields::getFields('sqlite', $config)
        );

        /***********************
         *
         * Driver: SSDB
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminSsdbFields::getFields('ssdb', $config)
        );

        /***********************
         *
         * Driver: WINCACHE
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminNoFieldFields::getFields('wincache', $config)
        );

        /***********************
         *
         * Driver: XCACHE
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminNoFieldFields::getFields('xcache', $config)
        );

        /***********************
         *
         * Driver: ZENDDISK
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminNoFieldFields::getFields('zenddisk', $config)
        );

        /***********************
         *
         * Driver: ZENDSHM
         *
         ***********************/
        $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = array_merge(
          $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ],
          PhpfastcacheAdminNoFieldFields::getFields('zendshm', $config)
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if($this->isAvailableDriver($form_state->getValue('phpfastcache_default_driver'))){
          try {
            /**
             * @todo: Pass parameters automatically
             */
            CacheManager::getInstance($form_state->getValue('phpfastcache_default_driver'));
          } catch (phpFastCacheDriverCheckException $e) {
            $form_state->setError($form, 'This driver is not usable at the moment, error code: ' . $e->getMessage());
          } catch (\Throwable $e) {
            $form_state->setError($form, 'This driver has encountered an error: ' . $e->getMessage());
          }
        }else{
          $form_state->setError($form, 'The driver chosen is unavailable !');
        }


        /**
         * Field Validation: phpfastcache_prefix
         */
        if(strlen($form_state->getValue('phpfastcache_prefix')) < 2)
        {
            $form_state->setError($form, 'The prefix must be 2 chars length minimum');
        }

        if(strlen($form_state->getValue('phpfastcache_prefix')) > 8)
        {
            $form_state->setError($form, 'The prefix must be 8 chars length maximum');
        }

        if(!preg_match('#' . self::PREFIX_REGEXP . '#', $form_state->getValue('phpfastcache_prefix')))
        {
            $form_state->setError($form, 'The prefix must contains only letters, numbers and underscore chars.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('phpfastcache.settings');
        $config->set('phpfastcache_enabled', (bool)$form_state->getValue('phpfastcache_enabled'))
          ->set('phpfastcache_env', (string)$form_state->getValue('phpfastcache_env'))
          ->set('phpfastcache_prefix', (string)$form_state->getValue('phpfastcache_prefix'))
          ->set('phpfastcache_default_ttl', (int)$form_state->getValue('phpfastcache_default_ttl'))
          ->set('phpfastcache_htaccess', (bool)$form_state->getValue('phpfastcache_htaccess'))
          ->set('phpfastcache_default_driver', (string)$form_state->getValue('phpfastcache_default_driver'))
          ->set('phpfastcache_bins', array_values(array_filter((array)$form_state->getValue('phpfastcache_bins'))))
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
            'host' => (string)$form_state->getValue('phpfastcache_drivers_config_couchbase_host'),
            'username' => (string)$form_state->getValue('phpfastcache_drivers_config_couchbase_username'),
            'password' => (string)$form_state->getValue('phpfastcache_drivers_config_couchbase_password'),
            'bucket' => (string)$form_state->getValue('phpfastcache_drivers_config_couchbase_bucket'),
            'bucket_password' => (string)$form_state->getValue('phpfastcache_drivers_config_couchbase_bucket_password'),
          ])
          /**
           * Files
           */
          ->set('phpfastcache_drivers_config.files', [
            'path' => (string)$form_state->getValue('phpfastcache_drivers_config_files_path'),
            'security_key' => (string)$form_state->getValue('phpfastcache_drivers_config_files_security_key'),
          ])
          /**
           * Leveldb
           */
          ->set('phpfastcache_drivers_config.leveldb', [
            'path' => (string)$form_state->getValue('phpfastcache_drivers_config_leveldb_path'),
            'security_key' => (string)$form_state->getValue('phpfastcache_drivers_config_leveldb_security_key'),
          ])
          /**
           * Memcache
           */
          ->set('phpfastcache_drivers_config.memcache', [
            'host' => (string)$form_state->getValue('phpfastcache_drivers_config_memcache_host'),
            'port' => (int)$form_state->getValue('phpfastcache_drivers_config_memcache_port'),
            'sasl_username' => (string)$form_state->getValue('phpfastcache_drivers_config_memcache_sasl_username'),
            'sasl_password' => (string)$form_state->getValue('phpfastcache_drivers_config_memcache_sasl_password'),
          ])
          /**
           * Memcached
           */
          ->set('phpfastcache_drivers_config.memcached', [
            'host' => (string)$form_state->getValue('phpfastcache_drivers_config_memcached_host'),
            'port' => (int)$form_state->getValue('phpfastcache_drivers_config_memcached_port'),
            'sasl_username' => (string)$form_state->getValue('phpfastcache_drivers_config_memcached_sasl_username'),
            'sasl_password' => (string)$form_state->getValue('phpfastcache_drivers_config_memcached_sasl_password'),
          ])
          /**
           * MongoDb
           */
          ->set('phpfastcache_drivers_config.mongodb', [
            'host' => (string)$form_state->getValue('phpfastcache_drivers_config_mongodb_host'),
            'port' => (int)$form_state->getValue('phpfastcache_drivers_config_mongodb_port'),
            'username' => (string)$form_state->getValue('phpfastcache_drivers_config_mongodb_username'),
            'password' => (string)$form_state->getValue('phpfastcache_drivers_config_mongodb_password'),
            'timeout' => (int)$form_state->getValue('phpfastcache_drivers_config_mongodb_timeout'),
          ])
          /**
           * Predis
           */
          ->set('phpfastcache_drivers_config.predis', [
            'host' => (string)$form_state->getValue('phpfastcache_drivers_config_predis_host'),
            'port' => (int)$form_state->getValue('phpfastcache_drivers_config_predis_port'),
            'password' => (string)$form_state->getValue('phpfastcache_drivers_config_predis_password'),
            'timeout' => (int)$form_state->getValue('phpfastcache_drivers_config_predis_timeout'),
            'dbindex' => (int)$form_state->getValue('phpfastcache_drivers_config_predis_dbindex'),
          ])
          /**
           * Redis
           */
          ->set('phpfastcache_drivers_config.redis', [
            'host' => (string)$form_state->getValue('phpfastcache_drivers_config_redis_host'),
            'port' => (int)$form_state->getValue('phpfastcache_drivers_config_redis_port'),
            'password' => (string)$form_state->getValue('phpfastcache_drivers_config_redis_password'),
            'timeout' => (int)$form_state->getValue('phpfastcache_drivers_config_redis_timeout'),
            'dbindex' => (int)$form_state->getValue('phpfastcache_drivers_config_redis_dbindex'),
          ])
          /**
           * Sqlite
           */
          ->set('phpfastcache_drivers_config.sqlite', [
            'path' => (string)$form_state->getValue('phpfastcache_drivers_config_sqlite_path'),
            'security_key' => (string)$form_state->getValue('phpfastcache_drivers_config_sqlite_security_key'),
          ])
          /**
           * Ssdb
           */
          ->set('phpfastcache_drivers_config.ssdb', [
            'host' => (string)$form_state->getValue('phpfastcache_drivers_config_ssdb_host'),
            'port' => (int)$form_state->getValue('phpfastcache_drivers_config_ssdb_port'),
            'password' => (string)$form_state->getValue('phpfastcache_drivers_config_ssdb_password'),
            'timeout' => (int)$form_state->getValue('phpfastcache_drivers_config_ssdb_timeout'),
          ])
          ->set('phpfastcache_drivers_config.wincache', [])
          ->set('phpfastcache_drivers_config.xcache', [])
          ->set('phpfastcache_drivers_config.zenddisk', [])
          ->set('phpfastcache_drivers_config.zendshm', [])
          ->save();

        parent::submitForm($form, $form_state);
    }

  /**
   * @param string $driverName
   *
   * @return bool
   */
    protected function isAvailableDriver(string $driverName): bool
    {
      try{
        /**
         * Mute the eventual notices/warning we
         * count encounter in some context when
         * using memcache and memcached or
         * redis and predis together for example
         */
        @CacheManager::getInstance($driverName);
        return true;
      }catch(PhpfastcacheDriverCheckException $e){
        return false;
      }catch(\Throwable $e){
        return true;
      }
    }
}
