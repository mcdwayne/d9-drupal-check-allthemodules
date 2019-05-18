<?php

namespace Drupal\entity_counter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\entity_counter\Plugin\EntityCounterRendererManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_reference_entity_counter' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_counter",
 *   label = @Translation("Entity counter formatter"),
 *   description = @Translation("Display an entity counter value."),
 *   field_types = {"entity_reference"}
 * )
 */
class EntityReferenceEntityCounterFormatter extends EntityReferenceEntityFormatter {

  /**
   * The entity counter renderer plugin manager.
   *
   * @var \Drupal\entity_counter\Plugin\EntityCounterRendererManager
   */
  protected $entityCounterRendererManager;

  /**
   * Constructs a EntityReferenceEntityCounterFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\entity_counter\Plugin\EntityCounterRendererManager $entity_counter_renderer_manager
   *   The entity counter renderer plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, EntityCounterRendererManager $entity_counter_renderer_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_type_manager, $entity_display_repository);

    $this->entityCounterRendererManager = $entity_counter_renderer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('plugin.manager.entity_counter.renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity counters.
    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');

    return $target_type == 'entity_counter';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // @TODO Add round type.
    return [
      'renderer_plugin' => 'plain',
      'renderer_settings' => [],
      'wrapper_tag' => 'div',
    ];
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated element.
   */
  public static function refreshRendererSettings(array &$form, FormStateInterface $form_state) {
    $renderer_element = $form_state->getTriggeringElement();

    $settings_form = NestedArray::getValue($form, array_slice($renderer_element['#array_parents'], 0, -1));

    return $settings_form['renderer_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form, $form_state);

    $field_name = $this->fieldDefinition->getName();
    $renders = $this->getRenderOptions();

    $form = FormatterBase::settingsForm($form, $form_state);
    $form += [
      '#tree' => TRUE,
      '#parents' => [
        'fields',
        $this->fieldDefinition->getName(),
        'settings_edit_form',
        'settings',
      ],
    ];
    $form['renderer_plugin'] = [
      '#type' => 'select',
      '#options' => $renders,
      '#title' => $this->t('Renderer plugin'),
      '#default_value' => $this->getSetting('renderer_plugin'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'refreshRendererSettings'],
        'wrapper' => $field_name . '-renderer-settings-wrapper',
        'effect' => 'fade',
      ],
    ];

    /** @var \Drupal\entity_counter\Plugin\EntityCounterRendererInterface $renderer */
    $renderer_plugin = $this->getSetting('renderer_plugin');
    $renderer_settings = $this->getSetting('renderer_settings');

    // Get selected value if exists.
    if (($triggering_element = $form_state->getTriggeringElement()) !== NULL &&
    substr($triggering_element['#name'], -strlen('settings][renderer_plugin]')) == 'settings][renderer_plugin]') {
      $renderer_plugin = $triggering_element['#value'];
      $renderer_settings = [];

      // Using entity embed button the field name changes between loads.
      $field_name = preg_replace('/-renderer-settings-wrapper$/', '', $triggering_element['#ajax']['wrapper']);
    }

    // Get the renderer and build its configuration form.
    $renderer = $this->entityCounterRendererManager->createInstance($renderer_plugin, ['settings' => $renderer_settings]);
    // If the renderer has not settings, return an empty container.
    if (!empty($renderer->defaultConfiguration())) {
      $form['renderer_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Plugin configuration'),
        '#open' => TRUE,
        '#attributes' => [
          'id' => [$field_name . '-renderer-settings-wrapper'],
        ],
        '#parents' => [
          'fields',
          $this->fieldDefinition->getName(),
          'settings_edit_form',
          'settings',
          'renderer_settings',
        ],
        '#element_validate' => [[$this, 'entityCounterFormatterFormValidate']],
      ];
      $subform_state = SubformState::createForSubform($form['renderer_settings'], $form, $form_state);
      $form['renderer_settings'] = $renderer->buildConfigurationForm($form['renderer_settings'], $subform_state);
    }
    else {
      $form['renderer_settings'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributtes' => [
          '#id' => [$field_name . '-renderer-settings-wrapper'],
        ],
      ];
    }

    $form['wrapper_tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HTML tag of the wrapper'),
      '#maxlength' => 255,
      '#default_value' => $this->getSetting('wrapper_tag'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Form API callback.
   */
  public function entityCounterFormatterFormValidate(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $submit_element = $form_state->getTriggeringElement();
    if ((!empty($submit_element['#op']) && $submit_element['#op'] == 'update' && $submit_element['#field_name'] == $this->fieldDefinition->getName()) ||
      // @TODO Try to make this more standard.
      (!empty($submit_element['#ajax']['callback']) && $submit_element['#ajax']['callback'] = '::submitEmbedStep')) {
      $renderer_plugin = $this->getSetting('renderer_plugin');
      $renderer_settings = $this->getSetting('renderer_settings');
      $renderer = $this->entityCounterRendererManager->createInstance($renderer_plugin, ['settings' => $renderer_settings]);

      // Validated the configuration stored in the 'renderer_settings'.
      $subform_state = SubformState::createForSubform($element, $completed_form, $form_state);
      $renderer->validateConfigurationForm($element, $subform_state);

      // Process configuration form state errors.
      $this->processConfigurationFormErrors($subform_state, $form_state);

      if (empty($form_state->getErrors())) {
        $renderer->submitConfigurationForm($element, $subform_state);

        // Update the original form values.
        $this->setSettings($subform_state->getValues());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $renderer_plugins = $this->getRenderOptions();
    $renderer_plugin = $this->getSetting('renderer_plugin');
    $summary[] = $this->t('Rendered as @plugin', ['@plugin' => isset($renderer_plugins[$renderer_plugin]) ? $renderer_plugins[$renderer_plugin] : $renderer_plugin]);
    $summary = array_merge($summary, $this->getRender($renderer_plugin, $this->getSetting('renderer_settings'))->getSummary());
    $summary[] = $this->t('HTML tag of the wrapper: @wrapper_tag', ['@wrapper_tag' => $this->getSetting('wrapper_tag')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity_counter) {
      $elements[$delta] = [
        '#type' => 'entity_counter',
        '#entity_counter' => $entity_counter->id(),
        '#renderer_plugin' => $this->getSetting('renderer_plugin'),
        '#renderer_settings' => $this->getSetting('renderer_settings'),
        '#wrapper_tag' => $this->getSetting('wrapper_tag'),
      ];
    }

    return $elements;
  }

  /**
   * Process configuration form errors in form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $configuration_state
   *   The loading bar style form state.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   The form state.
   */
  protected function processConfigurationFormErrors(FormStateInterface $configuration_state, FormStateInterface &$form_state) {
    foreach ($configuration_state->getErrors() as $name => $message) {
      $form_state->setErrorByName($name, $message);
    }
  }

  /**
   * Apply submitted form state to configuration.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function applyFormStateToConfiguration(array &$form, FormStateInterface $form_state) {
    $parents = [
      'fields',
      $this->fieldDefinition->getName(),
      'settings_edit_form',
      'settings',
    ];

    $configuration = $this->getSettings();
    $values = NestedArray::getValue($form_state->getValues(), $parents);
    $values = (empty($values)) ? $this->defaultSettings() : $values;

    $new_configuration = [];
    foreach ($configuration as $key => $value) {
      if (array_key_exists($key, $values)) {
        $new_configuration[$key] = $values[$key];
      }
      else {
        $new_configuration[$key] = NULL;
      }
    }

    $this->setSettings($new_configuration);
  }

  /**
   * Creates a pre-configured instance of a renderer plugin.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\entity_counter\Plugin\EntityCounterRendererInterface
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getRender($plugin_id, array $configuration = []) {
    $renders = $this->getRenderOptions();

    $render = NULL;
    if (array_key_exists($plugin_id, $renders)) {
      /** @var \Drupal\entity_counter\Plugin\EntityCounterRendererInterface $render */
      $render = $this->entityCounterRendererManager->createInstance($plugin_id, ['settings' => $configuration]);
    }

    return $render;
  }

  /**
   * Returns an array of entity counter renders.
   *
   * @return array
   *   An array of key value pairs suitable as '#options' for form elements.
   */
  protected function getRenderOptions() {
    $renders = [];
    foreach ($this->entityCounterRendererManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $renders[$plugin_id] = $plugin_definition['label'];
    }

    return $renders;
  }

}
