<?php

namespace Drupal\spamlinkblock\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for spamlinkblock module routes.
 */
class SpamlinkblockSettingsController extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * A cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a settings controller.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, CacheBackendInterface $cache_backend) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->cache = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('cache.default')
    );
  }

  /**
   * Get a value from the retrieved form settings array.
   */
  public function getFormSettingsValue($form_settings, $form_id) {
    // If there are settings in the array and the form ID already has a setting,
    // return the saved setting for the form ID.
    if (!empty($form_settings) && isset($form_settings[$form_id])) {
      return $form_settings[$form_id];
    }
    // Default to false.
    else {
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['spamlinkblock.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spamlinkblock_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Configuration.
    $form['configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SpamLinkBlock Configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['configuration']['protect_all_forms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect all forms with SpamLinkBlock'),
      '#description' => $this->t('Enable SpamLinkBlock protection for ALL forms on this site.'),
      '#default_value' => $this->config('spamlinkblock.settings')->get('protect_all_forms'),
    ];
    $form['configuration']['protect_anonymous_submissions_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect only forms submitted by unauthenticated users.'),
      '#description' => $this->t('Enable SpamLinkBlock protection for anonymous users only.'),
      '#default_value' => $this->config('spamlinkblock.settings')->get('protect_anonymous_submissions_only'),
    ];
    $form['configuration']['spam_stopwords'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Spam Stopwords'),
      '#default_value' => implode(PHP_EOL, $this->config('spamlinkblock.settings')->get('spam_stopwords')),
      '#description' => $this->t('In addition to blocking links you can add words that should also block form submissions. Each "stopword" should be on a separate line. Usage of wildcard "*" character is allowed.'),
    ];
    $form['configuration']['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log blocked form submissions'),
      '#description' => $this->t('Log submissions that are blocked by SpamLinkBlock.'),
      '#default_value' => $this->config('spamlinkblock.settings')->get('log'),
    ];

    // Enabled forms.
    $form_settings = $this->config('spamlinkblock.settings')->get('form_settings');
    $form['form_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SpamLinkBlock Enabled Forms'),
      '#description' => $this->t("Check the boxes next to individual forms to enable protection."),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#states' => [
        // Hide this fieldset when all forms are protected.
        'invisible' => [
          'input[name="protect_all_forms"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Generic forms.
    $form['form_settings']['general_forms'] = ['#markup' => '<h5>' . $this->t('General Forms') . '</h5>'];
    // User register form.
    $form['form_settings']['user_register_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User Registration form'),
      '#default_value' => $this->getFormSettingsValue($form_settings, 'user_register_form'),
    ];
    // User password form.
    $form['form_settings']['user_pass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User Password Reset form'),
      '#default_value' => $this->getFormSettingsValue($form_settings, 'user_pass'),
    ];

    // If webform.module enabled, add webforms.
    if ($this->moduleHandler->moduleExists('webform')) {
      $form['form_settings']['webforms'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Webforms (all)'),
        '#default_value' => $this->getFormSettingsValue($form_settings, 'webforms'),
      ];
    }

    // If contact.module enabled, add contact forms.
    if ($this->moduleHandler->moduleExists('contact')) {
      $form['form_settings']['contact_forms'] = ['#markup' => '<h5>' . $this->t('Contact Forms') . '</h5>'];

      $bundles = $this->entityTypeBundleInfo->getBundleInfo('contact_message');
      $formController = $this->entityTypeManager->getFormObject('contact_message', 'default');

      foreach ($bundles as $bundle_key => $bundle) {
        $stub = $this->entityTypeManager->getStorage('contact_message')->create([
          'contact_form' => $bundle_key,
        ]);
        $formController->setEntity($stub);
        $form_id = $formController->getFormId();

        $form['form_settings'][$form_id] = [
          '#type' => 'checkbox',
          '#title' => Html::escape($bundle['label']),
          '#default_value' => $this->getFormSettingsValue($form_settings, $form_id),
        ];
      }
    }

    // Node types for node forms.
    if ($this->moduleHandler->moduleExists('node')) {
      $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      if (!empty($types)) {
        // Node forms.
        $form['form_settings']['node_forms'] = ['#markup' => '<h5>' . $this->t('Node Forms') . '</h5>'];
        foreach ($types as $type) {
          $id = 'node_' . $type->get('type') . '_form';
          $form['form_settings'][$id] = [
            '#type' => 'checkbox',
            '#title' => $this->t('@name node form', ['@name' => $type->label()]),
            '#default_value' => $this->getFormSettingsValue($form_settings, $id),
          ];
        }
      }
    }

    // Comment types for comment forms.
    if ($this->moduleHandler->moduleExists('comment')) {
      $types = $this->entityTypeManager->getStorage('comment_type')->loadMultiple();
      if (!empty($types)) {
        $form['form_settings']['comment_forms'] = ['#markup' => '<h5>' . $this->t('Comment Forms') . '</h5>'];
        foreach ($types as $type) {
          $id = 'comment_' . $type->id() . '_form';
          $form['form_settings'][$id] = [
            '#type' => 'checkbox',
            '#title' => $this->t('@name comment form', ['@name' => $type->label()]),
            '#default_value' => $this->getFormSettingsValue($form_settings, $id),
          ];
        }
      }
    }

    // Store the keys we want to save in configuration when form is submitted.
    $keys_to_save = array_keys($form['configuration']);
    foreach ($keys_to_save as $key => $key_to_save) {
      if (strpos($key_to_save, '#') !== FALSE) {
        unset($keys_to_save[$key]);
      }
    }
    $form_state->setStorage(['keys' => $keys_to_save]);

    // For now, manually add submit button. Hopefully, by the time D8 is
    // released, there will be something like system_settings_form() in D7.
    $form['actions']['#type'] = 'container';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('spamlinkblock.settings');
    $storage = $form_state->getStorage();

    // Save configuration items from $form_state.
    foreach ($form_state->getValues() as $key => $value) {
      if (in_array($key, $storage['keys'])) {
        if ($key == 'spam_stopwords') {
          $config->set($key, explode(PHP_EOL, $value));
        }
        else {
          $config->set($key, $value);
        }
      }
    }

    // Save everything from $form_state into a 'form_settings' array.
    $config->set('form_settings', $form_state->getValue('form_settings'));

    $config->save();

    // Clear cache.
    $this->cache->delete('spamlinkblock_protected_forms');

    // Tell the user the settings have been saved.
    drupal_set_message($this->t('The configuration has been saved.'));
  }

}
