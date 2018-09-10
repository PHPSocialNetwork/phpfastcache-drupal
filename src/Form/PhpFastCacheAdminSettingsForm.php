<?php

namespace Drupal\phpfastcache\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\phpfastcache\Cache\PhpfastcacheBackendFactory;
use Drupal\phpfastcache\Form\Fields\PhpfastcacheAdminFieldClassMap;
use Drupal\phpfastcache\CacheManager;
use Phpfastcache\Api as PhpfastcacheApi;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure phpfastcache settings for this site.
 * @todo Uncamelize class name...
 */
class PhpFastCacheAdminSettingsForm extends ConfigFormBase {

  const PREFIX_REGEXP = '^\w*$';

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * PhpFastCacheAdminSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Extension\ModuleHandler       $moduleHandler
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandler $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

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
    $randomService = new Random();
    $config        = $this->config('phpfastcache.settings');

    if (!phpfastcache_is_library_installed()) {
      \Drupal::messenger()->addError('The Phpfastcache library is not installed.');
      $config->set('phpfastcache_enabled', FALSE)
             ->save();

      return parent::buildForm($form, $form_state);
    }

    if (!phpfastcache_is_settings_php_configured()) {
      \Drupal::messenger()->addError('The cache backend has not been yet configured to use PhpFastCache.');
      \Drupal::messenger()->addError('Please read this topic to learn more: https://api.drupal.org/api/drupal/core!core.api.php/group/cache/8.2.x');
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
      '#type'  => 'details',
      '#title' => $this->t('General settings'),
      '#open'  => TRUE,
    ];

    $form[ 'general' ][ 'phpfastcache_enabled' ] = [
      '#default_value' => (int) $config->get('phpfastcache_enabled'),
      '#description'   => $this->t('Enable or disable all the PhpFastCache components'),
      '#required'      => TRUE,
      '#options'       => [
        '0' => t('No'),
        '1' => t('Yes'),
      ],
      '#title'         => $this->t('PhpFastCache enabled'),
      '#type'          => 'select',
    ];

    /***********************
     *
     * Drivers wrapper
     *
     ***********************/
    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ] = [
      '#type'   => 'container',
      '#states' => [
        'invisible' => [
          'select[name="phpfastcache_enabled"]' => ['value' => '0'],
        ],
      ],
    ];

    if($this->moduleHandler->moduleExists('devel')){
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_env' ] = [
        '#default_value' => (string) $config->get('phpfastcache_env'),
        '#description'   => $this->t(
          '<strong>Production</strong>: Will displays minimal information in case of failure.<br />
                                    <strong>Development</strong>: Will displays very verbose information in case of failure.'
        ),
        '#required'      => TRUE,
        '#options'       => [
          PhpfastcacheBackendFactory::ENV_DEV  => t('Development'),
          PhpfastcacheBackendFactory::ENV_PROD => t('Production'),
        ],
        '#title'         => $this->t('PhpFastCache environment'),
        '#type'          => 'select',
      ];
    }else if((string) $config->get('phpfastcache_env') === PhpfastcacheBackendFactory::ENV_DEV){
      $config->set('phpfastcache_env', PhpfastcacheBackendFactory::ENV_PROD)
             ->save();
    }

    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_prefix' ] = [
      '#default_value' => (string) ($config->get('phpfastcache_prefix') ?: $randomService->name(6, TRUE)),
      '#description'   => $this->t(
        'The cache keyspace prefix that will be used to identify this website. 
                                    This value length <strong>MUST</strong> be up to 8 chars and 2 chars minimum. <br />
                                    This value <strong>MUST</strong> be unique depending your other Drupal installations on this cache backend. <br />
                                    This value <strong>MUST</strong> be alpha-numeric (' . self::PREFIX_REGEXP . ')'
      ),
      '#required'      => TRUE,
      '#title'         => $this->t('Cache keyspace prefix'),
      '#type'          => 'textfield',
    ];

    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_default_ttl' ] = [
      '#default_value' => (int) $config->get('phpfastcache_default_ttl'),
      '#description'   => $this->t('Default TTL is no one is specified.'),
      '#required'      => TRUE,
      '#title'         => $this->t('Default <abbr title="Time to live">TTL</abbr>'),
      '#type'          => 'textfield',
    ];

    $driversOption = [];
    foreach (CacheManager::getDriverList() as $systemDriver) {
      if ($this->isAvailableDriver($systemDriver)) {
        $driversOption[ strtolower($systemDriver) ] = t(ucfirst($systemDriver));
      }
      else {
        $driversOption[ strtolower($systemDriver) ] = t(ucfirst($systemDriver)) . ' [UNAVAILABLE]';
      }
    }
    ksort($driversOption);

    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_default_driver' ] = [
      '#default_value' => (string) $config->get('phpfastcache_default_driver'),
      '#description'   => $this->t('Enable or disable all the PhpFastCache components.'),
      '#required'      => TRUE,
      '#options'       => $driversOption,
      '#title'         => $this->t('Cache driver'),
      '#type'          => 'select',
    ];

    /***********************
     *
     * Drivers settings wrapper
     *
     ***********************/

    $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ] = [
      '#type' => 'container',
    ];

    foreach (\array_keys($driversOption) as $driverName) {
      $form[ 'general' ][ 'phpfastcache_settings_wrapper' ][ 'phpfastcache_driver_details' ][] =
        PhpfastcacheAdminFieldClassMap::getClass($driverName)
                                      ::getFields($driverName, $config);
    }

    $phpfastcacheInfo = system_get_info('module', 'webprofiler');

    $form[ 'general' ][ 'credit' ] = [
      '#markup' => '<div class="text-right"><small>Phpfastcache module v'
                   . ($phpfastcacheInfo['version'] ?? $phpfastcacheInfo['core'] ?? ' Unknown')
                   . ' (Lib v' . PhpfastcacheApi::getPhpFastCacheVersion()
                   . ' , API v' . PhpfastcacheApi::getVersion()
                   . ')</small></div>',
    ];

    /**
     * Field group dependency
     */
    $form[ '#attached' ][ 'library' ][] = 'phpfastcache/phpfastcache.admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->isAvailableDriver($form_state->getValue('phpfastcache_default_driver'))) {
      try {
        /**
         * @todo: Call PhpfastcacheInstanceBuilder here
         */
        @CacheManager::getInstance($form_state->getValue('phpfastcache_default_driver'));
      } catch (PhpfastcacheDriverCheckException $e) {
        $form_state->setError($form, 'This driver is not usable at the moment, error code: ' . $e->getMessage());
      } catch (\Throwable $e) {
        $form_state->setError($form, 'This driver has encountered an error: ' . $e->getMessage());
      }
    }
    else {
      $form_state->setError($form, 'The driver chosen is unavailable !');
    }

    /**
     * Field Validation: phpfastcache_prefix
     */
    if (\strlen($form_state->getValue('phpfastcache_prefix')) < 2) {
      $form_state->setError($form, 'The prefix must be 2 chars length minimum');
    }

    if (\strlen($form_state->getValue('phpfastcache_prefix')) > 8) {
      $form_state->setError($form, 'The prefix must be 8 chars length maximum');
    }

    if (!\preg_match('#' . self::PREFIX_REGEXP . '#', $form_state->getValue('phpfastcache_prefix'))) {
      $form_state->setError($form, 'The prefix must contains only letters, numbers and underscore chars.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('phpfastcache.settings');
    $config->set('phpfastcache_enabled', (bool) $form_state->getValue('phpfastcache_enabled'))
           ->set('phpfastcache_prefix', (string) $form_state->getValue('phpfastcache_prefix'))
           ->set('phpfastcache_default_ttl', (int) $form_state->getValue('phpfastcache_default_ttl'))
           ->set('phpfastcache_default_driver', (string) $form_state->getValue('phpfastcache_default_driver'));

    if($this->moduleHandler->moduleExists('devel')){
      $config->set('phpfastcache_env', (string) $form_state->getValue('phpfastcache_env'));
    }
    /*****************
     * Drivers settings
     *****************/
    foreach (CacheManager::getDriverList() as $driverName) {
      $driverName = \strtolower($driverName);
      PhpfastcacheAdminFieldClassMap::getClass($driverName)
                                    ::setConfig($driverName, $form_state, $config);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * @param string $driverName
   *
   * @return bool
   */
  protected function isAvailableDriver(string $driverName): bool {
    try {
      /**
       * Mute the eventual notices/warning we
       * count encounter in some context when
       * using memcache and memcached or
       * redis and predis together for example
       */
      $i = @CacheManager::getInstance($driverName);
      return TRUE;
    } catch (PhpfastcacheDriverCheckException $e) {
      return FALSE;
    } catch (\Throwable $e) {
      return TRUE;
    }
  }
}
