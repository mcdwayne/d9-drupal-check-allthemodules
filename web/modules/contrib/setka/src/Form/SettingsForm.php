<?php

namespace Drupal\setka_editor\Form;

use Drupal\Core\Asset\CssCollectionOptimizer;
use Drupal\Core\Asset\JsCollectionOptimizer;
use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Link;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Drupal\setka_editor\SetkaEditorApi;
use Drupal\setka_editor\SetkaEditorHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures Setka Editor settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Setka Editor api service.
   *
   * @var \Drupal\setka_editor\SetkaEditorApi
   */
  protected $editorApi;

  /**
   * Cache Discovery bin backend.
   *
   * @var \Drupal\Core\Cache\DatabaseBackend
   */
  protected $cacheDiscovery;

  /**
   * Drupal CSS optimizer service.
   *
   * @var \Drupal\Core\Asset\CssCollectionOptimizer
   */
  protected $cssOptimizer;

  /**
   * Drupal JS optimizer service.
   *
   * @var \Drupal\Core\Asset\JsCollectionOptimizer
   */
  protected $jsOptimizer;

  /**
   * Drupal file_system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Drupal state service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Drupal queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscovery
   */
  protected $libraryDiscovery;

  /**
   * Lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory,
                              SetkaEditorApi $editorApi,
                              DatabaseBackend $cacheDiscovery,
                              CssCollectionOptimizer $cssOptimizer,
                              JsCollectionOptimizer $jsOptimizer,
                              FileSystem $fileSystem,
                              State $state,
                              QueueFactory $queueFactory,
                              LibraryDiscovery $libraryDiscovery,
                              LockBackendInterface $lock) {
    parent::__construct($configFactory);
    $this->editorApi = $editorApi;
    $this->cacheDiscovery = $cacheDiscovery;
    $this->cssOptimizer = $cssOptimizer;
    $this->jsOptimizer = $jsOptimizer;
    $this->fileSystem = $fileSystem;
    $this->state = $state;
    $this->queueFactory = $queueFactory;
    $this->libraryDiscovery = $libraryDiscovery;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('setka_editor.api'),
      $container->get('cache.discovery'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('file_system'),
      $container->get('state'),
      $container->get('queue'),
      $container->get('library.discovery'),
      $container->get('lock')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'setka_editor_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'setka_editor.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('setka_editor.settings');
    $setkaEditorVersion = $config->get('setka_editor_version');
    if ($setkaEditorVersion) {
      $form['setka_license_description'] = [
        '#markup' => '<div class="messages messages--status">' . $this->t('Current Setka Editor version: @version', ['@version' => $setkaEditorVersion]) . '</div>',
      ];
    }
    else {
      $link = Link::fromTextAndUrl('https://editor.setka.io', Url::fromUri('https://editor.setka.io'))->toString();
      $licenseDescription = $this->t('To activate Setka Editor you need to register at @link. After registration, you will receive a unique license key, which must be inserted in the box below. If you already have a license key, use it to activate it.',
        ['@link' => $link]
      );
      $form['setka_license_description'] = [
        '#markup' => '<p>' . $licenseDescription . '</p>',
      ];
    }
    $form['setka_license_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('License key'),
      '#default_value' => $config->get('setka_license_key'),
      '#empty_value' => '',
      '#maxlength' => 255,
      '#required' => FALSE,
      '#description' => $this->t('You may find the license key in your personal account.'),
    ];
    $setkaUseCdn = $config->get('setka_use_cdn');
    $setkaUseCdnDisabled = !SetkaEditorHelper::checkSetkaFolderPermissions($this->fileSystem);
    $form['setka_use_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use files from Setka CDN'),
      '#default_value' => $setkaUseCdn,
      '#disabled' => $setkaUseCdnDisabled,
      '#description' => $this->t('If option is checked module will use css/js files from setka.io cdn, otherwise files will be loaded on site.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $setka_license_key = $form_state->getValue('setka_license_key');
    if (!empty($setka_license_key)) {
      if (!$this->editorApi->getCurrentBuild($setka_license_key)) {
        $link = $this->getLinkGenerator()->generate('https://editor.setka.io/support', Url::fromUri('https://editor.setka.io/support'));
        $licenseText = $this->t('It seems that something went wrong. Contact customer support: @link.',
          ['@link' => $link]
        );
        $form_state->setErrorByName('setka_license_key', $licenseText);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $originalLicenseKey = $this->config('setka_editor.settings')->get('setka_license_key');

    $downloadFiles = (!$values['setka_use_cdn'] && SetkaEditorHelper::checkSetkaFolderPermissions($this->fileSystem));
    if (!empty($values['setka_license_key'])) {
      if ($currentBuild = $this->editorApi->getCurrentBuild($values['setka_license_key'])) {
        $parsedValues = SetkaEditorHelper::parseStyleManagerData($currentBuild);
        $values = array_merge($values, $parsedValues);
      }
    }
    if (!empty($values['setka_editor_js_cdn']) && !empty($values['setka_editor_css_cdn']) &&
      !empty($values['setka_company_css_cdn']) && !empty($values['setka_company_json_cdn'] &&
        !empty($values['setka_public_js_cdn']))) {
      if (empty($originalLicenseKey) || ($originalLicenseKey != $values['setka_license_key'])) {
        $this->editorApi->pushSystemInfo($values['setka_license_key']);
      }
      if ($downloadFiles) {
        $queue = $this->queueFactory->get('update_setke_editor');
        if (!$queue->numberOfItems()) {
          $queue->createQueue();
        }
        $queue->createItem(['newSettings' => $values]);
        $this->getLogger('setka_editor')->info('Setka Editor config update: config update task added to queue.');
        if ($this->lock->acquire('setka_editor_files_update')) {
          while ($newSettingsItem = $queue->claimItem()) {
            $newSettingsData = $newSettingsItem->data['newSettings'];
            SetkaEditorHelper::buildSetkaFilesUpdateTask($this->config('setka_editor.settings'), $this->state, $newSettingsData);
            $editableConfig = $this->configFactory->getEditable('setka_editor.settings');
            if (isset($newSettingsData['setka_license_key'])) {
              $editableConfig->set('setka_license_key', $newSettingsData['setka_license_key']);
            }
            if (isset($newSettingsData['setka_use_cdn'])) {
              $editableConfig->set('setka_use_cdn', $newSettingsData['setka_use_cdn']);
            }
            $editableConfig
              ->set('setka_editor_version', $newSettingsData['setka_editor_version'])
              ->set('setka_editor_public_token', $newSettingsData['setka_editor_public_token'])
              ->set('setka_company_meta_data', $newSettingsData['setka_company_meta_data'])
              ->set('setka_editor_js_cdn', $newSettingsData['setka_editor_js_cdn'])
              ->set('setka_editor_css_cdn', $newSettingsData['setka_editor_css_cdn'])
              ->set('setka_company_css_cdn', $newSettingsData['setka_company_css_cdn'])
              ->set('setka_company_json_cdn', $newSettingsData['setka_company_json_cdn'])
              ->set('setka_public_js_cdn', $newSettingsData['setka_public_js_cdn'])
              ->save();
            $this->libraryDiscovery->clearCachedDefinitions();
            $this->configFactory->reset('setka_editor.settings');
            SetkaEditorHelper::runSetkaFilesUpdateTask($this->state);
            $queue->deleteItem($newSettingsItem);
          }
          parent::submitForm($form, $form_state);
          $this->getLogger('setka_editor')->info('Setka Editor config update: successful update!');
          foreach (Cache::getBins() as $cache_backend) {
            $cache_backend->deleteAll();
          }
          $this->libraryDiscovery->clearCachedDefinitions();
          $this->configFactory->reset('setka_editor.settings');
          $this->cacheDiscovery->deleteAll();
          $this->cssOptimizer->deleteAll();
          $this->jsOptimizer->deleteAll();
          _drupal_flush_css_js();

          $this->lock->release('setka_editor_files_update');
        }
      }
      else {
        $this->config('setka_editor.settings')
          ->set('setka_license_key', $values['setka_license_key'])
          ->set('setka_editor_version', $values['setka_editor_version'])
          ->set('setka_editor_public_token', $values['setka_editor_public_token'])
          ->set('setka_company_meta_data', $values['setka_company_meta_data'])
          ->set('setka_editor_js_cdn', $values['setka_editor_js_cdn'])
          ->set('setka_editor_css_cdn', $values['setka_editor_css_cdn'])
          ->set('setka_company_css_cdn', $values['setka_company_css_cdn'])
          ->set('setka_company_json_cdn', $values['setka_company_json_cdn'])
          ->set('setka_public_js_cdn', $values['setka_public_js_cdn'])
          ->set('setka_use_cdn', $values['setka_use_cdn'])
          ->save();
        $this->state->setMultiple(
          [
            'setka_editor_js' => FALSE,
            'setka_editor_css' => FALSE,
            'setka_company_css' => FALSE,
            'setka_company_json' => FALSE,
            'setka_public_js' => FALSE,
          ]
        );
        parent::submitForm($form, $form_state);
        foreach (Cache::getBins() as $cache_backend) {
          $cache_backend
            ->deleteAll();
        }
        $this->libraryDiscovery->clearCachedDefinitions();
        $this->configFactory->reset('setka_editor.settings');
        $this->cacheDiscovery->deleteAll();
        $this->cssOptimizer->deleteAll();
        $this->jsOptimizer->deleteAll();
        _drupal_flush_css_js();
      }

      if (empty($originalLicenseKey)) {
        $this->messenger()->addMessage($this->t('Setka Editor license key activated successfully!'));
      }
      elseif ($originalLicenseKey != $values['setka_license_key']) {
        $this->messenger()->addMessage($this->t('Setka Editor license key updated successfully!'));
      }
      else {
        $this->messenger()->addMessage($this->t('Setka Editor configuration updated successfully!'));
      }
    }
    else {
      $this->config('setka_editor.settings')->delete()->save();
      $this->config('setka_editor.settings')
        ->set('setka_license_key', $values['setka_license_key'])
        ->set('setka_use_cdn', $values['setka_use_cdn'])
        ->save();
    }
  }

  /**
   * Returns CDN Setka Editor config value if local is empty.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Immutable config.
   * @param \Drupal\Core\State\State $state
   *   Drupal state service.
   * @param string $confName
   *   Config value name.
   * @param bool $libraryFormat
   *   TRUE - returns library formatted array, FALSE - returns URL string.
   * @param bool $loadAsync
   *   Load library asynchronously or not.
   *
   * @return string|null
   *   Config value.
   */
  public static function getConfigValue(ImmutableConfig $config, State $state, $confName, $libraryFormat = FALSE, $loadAsync = FALSE) {
    $attributes = [];
    if ($loadAsync) {
      $attributes = ['async' => TRUE];
    }

    $setkaUseCdn = TRUE;
    if (\Drupal::lock()->lockMayBeAvailable('setka_editor_files_update')) {
      $setkaUseCdn = $config->get('setka_use_cdn');
    }

    if (!$setkaUseCdn) {
      $confValue = $state->get($confName);
      if (empty($confValue)) {
        $confValue = $config->get($confName . '_cdn');
        if (!empty($confValue) && $libraryFormat) {
          $confValue = [
            $confValue => [
              'type' => 'external',
              'scope' => 'header',
              'attributes' => $attributes,
            ],
          ];
        }
      }
      elseif ($libraryFormat) {
        $confValue = [$confValue => ['scope' => 'header', 'attributes' => $attributes]];
      }
    }
    else {
      $confValue = $config->get($confName . '_cdn');
      if (!empty($confValue) && $libraryFormat) {
        $confValue = [
          $confValue => [
            'type' => 'external',
            'scope' => 'header',
            'attributes' => $attributes,
          ],
        ];
      }
    }

    return $confValue;
  }

}
