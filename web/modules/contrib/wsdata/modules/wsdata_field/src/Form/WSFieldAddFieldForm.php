<?php

namespace Drupal\wsdata_field\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field_ui\FieldUI;
use Drupal\field_ui\Form\FieldStorageAddForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the "field storage" add page.
 */
class WSFieldAddFieldForm extends FieldStorageAddForm {

  /**
   * Constructs a new FieldStorageAddForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_manager, $field_type_plugin_manager, $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wsfield_field_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');

    // Gather valid field types.
    $field_type_options = [];
    foreach ($this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions()) as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        $field_type_options[$category][$name] = $field_type['label'];
      }
    }

    $form['add'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
    ];

    $form['add']['new_storage_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a new field'),
      '#options' => $field_type_options,
      '#empty_option' => $this->t('- Select a field type -'),
    ];

    // Field label and field_name.
    $form['new_storage_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        '!visible' => [
          ':input[name="new_storage_type"]' => ['value' => ''],
        ],
      ],
    ];
    $form['new_storage_wrapper']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 15,
    ];

    $field_prefix = $this->config('field_ui.settings')->get('field_prefix');
    $form['new_storage_wrapper']['field_name'] = [
      '#type' => 'machine_name',
      // This field should stay LTR even for RTL languages.
      '#field_prefix' => '<span dir="ltr">' . $field_prefix,
      '#field_suffix' => '</span>&lrm;',
      '#size' => 15,
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      // Calculate characters depending on the length of the field prefix
      // setting. Maximum length is 32.
      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
      '#machine_name' => [
        'source' => ['new_storage_wrapper', 'label'],
        'exists' => [$this, 'fieldNameExists'],
      ],
      '#required' => FALSE,
    ];

    // Place the 'translatable' property as an explicit value so that contrib
    // modules can form_alter() the value for newly created fields. By default
    // we create field storage as translatable so it will be possible to enable
    // translation at field level.
    $form['translatable'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error = FALSE;
    $values = $form_state->getValues();
    $destinations = [];
    $entity_type = $this->entityManager->getDefinition($this->entityTypeId);

    // Create new field.
    if ($values['new_storage_type']) {
      $field_storage_values = [
        'field_name' => $values['field_name'],
        'entity_type' => $this->entityTypeId,
        'type' => $values['new_storage_type'],
        'translatable' => $values['translatable'],
        'custom_storage' => TRUE,
      ];
      $field_values = [
        'field_name' => $values['field_name'],
        'entity_type' => $this->entityTypeId,
        'bundle' => $this->bundle,
        'label' => $values['label'],
        // Field translatability should be explicitly enabled by the users.
        'translatable' => FALSE,
      ];
      $widget_id = $formatter_id = NULL;

      // Check if we're dealing with a preconfigured field.
      if (strpos($field_storage_values['type'], 'field_ui:') !== FALSE) {
        list(, $field_type, $option_key) = explode(':', $field_storage_values['type'], 3);
        $field_storage_values['type'] = $field_type;

        $field_type_class = $this->fieldTypePluginManager->getDefinition($field_type)['class'];
        $field_options = $field_type_class::getPreconfiguredOptions()[$option_key];

        // Merge in preconfigured field storage options.
        if (isset($field_options['field_storage_config'])) {
          foreach (['cardinality', 'settings'] as $key) {
            if (isset($field_options['field_storage_config'][$key])) {
              $field_storage_values[$key] = $field_options['field_storage_config'][$key];
            }
          }
        }

        // Merge in preconfigured field options.
        if (isset($field_options['field_config'])) {
          foreach (['required', 'settings'] as $key) {
            if (isset($field_options['field_config'][$key])) {
              $field_values[$key] = $field_options['field_config'][$key];
            }
          }
        }

        $widget_id = isset($field_options['entity_form_display']['type']) ? $field_options['entity_form_display']['type'] : NULL;
        $formatter_id = isset($field_options['entity_view_display']['type']) ? $field_options['entity_view_display']['type'] : NULL;
      }

      // Create the field storage and field.
      try {

        $this->entityManager->getStorage('field_storage_config')->create($field_storage_values)->save();
        $field = $this->entityManager->getStorage('field_config')->create($field_values);
        $field->save();

        $this->configureEntityFormDisplay($values['field_name'], $widget_id);
        $this->configureEntityViewDisplay($values['field_name'], $formatter_id);

        // Always show the field settings step, as the cardinality needs to be
        // configured for new fields.
        $route_parameters = [
          'field_config' => $field->id(),
        ] + FieldUI::getRouteBundleParameter($entity_type, $this->bundle);
        $destinations[] = ['route_name' => "entity.field_config.{$this->entityTypeId}_wsfield_edit_form", 'route_parameters' => $route_parameters];
        $destinations[] = ['route_name' => "entity.field_config.{$this->entityTypeId}_storage_edit_form", 'route_parameters' => $route_parameters];
        $destinations[] = ['route_name' => "entity.field_config.{$this->entityTypeId}_field_edit_form", 'route_parameters' => $route_parameters];
        $destinations[] = ['route_name' => "entity.{$this->entityTypeId}.field_ui_fields", 'route_parameters' => $route_parameters];

        // Store new field information for any additional submit handlers.
        $form_state->set(['fields_added', '_add_new_field'], $values['field_name']);
      }
      catch (\Exception $e) {
        $error = TRUE;
        drupal_set_message($this->t('There was a problem creating field %label: @message', ['%label' => $values['label'], '@message' => $e->getMessage()]), 'error');
      }
    }

    if ($destinations) {
      $destination = $this->getDestinationArray();
      $destinations[] = $destination['destination'];
      $form_state->setRedirectUrl(FieldUI::getNextDestination($destinations, $form_state));
    }
    elseif (!$error) {
      drupal_set_message($this->t('Your settings have been saved.'));
    }
  }
}
