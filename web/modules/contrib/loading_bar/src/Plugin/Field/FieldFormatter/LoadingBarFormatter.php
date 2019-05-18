<?php

namespace Drupal\loading_bar\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\loading_bar\Form\LoadingBarConfigurationForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'loading_bar' formatter.
 *
 * @FieldFormatter(
 *   id = "loading_bar",
 *   label = @Translation("Loading bar"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class LoadingBarFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The loading bar configuration form.
   *
   * @var \Drupal\loading_bar\Form\LoadingBarConfigurationForm
   */
  protected $configurationForm;

  /**
   * Constructs a LoadingBarFormatter object.
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
   *   Any third party settings.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ContainerInterface $container) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->configurationForm = LoadingBarConfigurationForm::create($container, $settings);
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
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return LoadingBarConfigurationForm::defaultConfiguration() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['loading_bar'] = [
      '#tree' => TRUE,
      '#parents' => [
        'fields',
        $this->fieldDefinition->getName(),
        'settings_edit_form',
        'settings',
      ],
      '#element_validate' => [[$this, 'loadingBarSettingsFormValidate']],
    ];
    $subform_state = SubformState::createForSubform($elements['loading_bar'], $elements, $form_state);
    $elements['loading_bar'] = $this->configurationForm->buildForm($elements['loading_bar'], $subform_state);

    return $elements;
  }

  /**
   * Form API callback.
   */
  public function loadingBarSettingsFormValidate(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $submit_element = $form_state->getTriggeringElement();
    if (!empty($submit_element['#op']) && $submit_element['#op'] == 'update' && $submit_element['#field_name'] == $this->fieldDefinition->getName()) {
      // The loading bar style configuration is stored in the 'settings'.
      $subform_state = SubformState::createForSubform($element, $completed_form, $form_state);
      $this->configurationForm->validateForm($element, $subform_state);

      // Process configuration form state errors.
      $this->processConfigurationFormErrors($subform_state, $form_state);

      if (empty($form_state->getErrors())) {
        $this->configurationForm->submitForm($element, $subform_state);

        // Update the original form values.
        $this->setSettings($subform_state->getValues());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $configuration = $this->getSettings();
    $configuration['height'] = '100%';
    $summary[] = [
      '#type' => 'loading_bar',
      '#configuration' => $configuration,
      '#value' => 50,
      '#attributes' => [
        'class' => ['loading-bar-demo'],
      ],
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'loading_bar',
        '#configuration' => $this->getSettings(),
        '#value' => $item->value,
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

}
