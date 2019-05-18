<?php

namespace Drupal\funnelback\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Funnelback settings form.
 */
class FunnelbackSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FunnelbackSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'funnelback_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'funnelback.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('funnelback.settings');

    $form['description'] = [
      '#value' => 'These are the settings for Funnelback search integration.',
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];

    $form['funnelback_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base Url'),
      '#description' => $this->t('The base url for the Funnelback interface (excluding trailing slash). For example: https://example.funnelback.com/'),
      '#size' => 60,
      '#maxlength' => 255,
      '#default_value' => $config->get('general_settings.base_url'),
      '#required' => TRUE,
    ];

    $form['funnelback_collection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection Name'),
      '#description' => $this->t('The Funnelback collection name'),
      '#size' => 30,
      '#maxlength' => 255,
      '#default_value' => $config->get('general_settings.collection'),
      '#required' => TRUE,
    ];

    $form['funnelback_profile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Profile Name'),
      '#description' => $this->t('Funnelback profile name'),
      '#size' => 30,
      '#maxlength' => 255,
      '#default_value' => $config->get('general_settings.profile'),
      '#required' => TRUE,
    ];

    $form['funnelback_autocomplete'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Autocomplete settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['funnelback_autocomplete']['funnelback_autocomplete_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-completion'),
      '#default_value' => $config->get('autocomplete.enabled'),
    ];

    $form['funnelback_autocomplete']['funnelback_autocomplete_results'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Results number'),
      '#description' => $this->t('Set the number of results that autocomplete will popup.'),
      '#size' => 30,
      '#default_value' => $config->get('autocomplete.results'),
    ];

    $form['funnelback_result'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Result display settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['funnelback_result']['funnelback_enable_display_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use display mode to render results'),
      '#default_value' => $config->get('display_mode.enabled'),
    ];

    // Get all view modes list.
    $entityViewModes = $this->entityTypeManager->getStorage('entity_view_mode')->loadMultiple();
    $viewModes = [];
    foreach ($entityViewModes as $entityViewMode) {
      if (strpos($entityViewMode->id(), 'node.') === 0) {
        $viewModes[$entityViewMode->id()] = $entityViewMode->label();
      }
    }

    $form['funnelback_result']['funnelback_display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Select display mode to render you search results'),
      '#description' => $this->t('You will need to add nodeId to your metamap in funnelback admin dashboard to use this feature. Content from remote site will use default search result layout.'),
      '#options' => $viewModes,
      '#default_value' => $config->get('display_mode.id'),
    ];

    $form['funnelback_result']['funnelback_custom_template'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Custom template name if you are using a custom template. Warning: This is an experimental feature, related fields can be missing in custom template, make sure all necessary fields are included in your template.'),
      '#title' => $this->t('Custom template name'),
      '#default_value' => $config->get('general_settings.custom_template', NULL),
    ];

    $form['funnelback_result']['funnelback_no_result_text'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Full HTML format is supported in this field. Use [funnelback-query] as token for the current search query.'),
      '#title' => $this->t('Text when no result found'),
      '#default_value' => $config->get('general_settings.no_result_text'),
    ];

    $form['funnelback_debug_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Debugging'),
      '#options' => [
        'none' => $this->t('None'),
        'log' => $this->t('Log requests'),
        'verbose' => $this->t('Verbose output'),
      ],
      '#default_value' => $config->get('general_settings.debug_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the configuration.
    $this->configFactory->getEditable('example.settings')
      ->set('general_settings.base_url', $form_state->getValue('general_settings.base_url'))
      ->set('general_settings.collection', $form_state->getValue('general_settings.collection'))
      ->set('general_settings.profile', $form_state->getValue('general_settings.profile'))
      ->set('autocomplete.enabled', $form_state->getValue('autocomplete.enabled'))
      ->set('autocomplete.results', $form_state->getValue('autocomplete.results'))
      ->set('display_mode.enabled', $form_state->getValue('display_mode.enabled'))
      ->set('display_mode.id', $form_state->getValue('display_mode.id'))
      ->set('general_settings.custom_template', $form_state->getValue('general_settings.custom_template'))
      ->set('general_settings.no_result_text', $form_state->getValue('general_settings.no_result_text'))
      ->set('general_settings.debug_mode', $form_state->getValue('general_settings.debug_mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
