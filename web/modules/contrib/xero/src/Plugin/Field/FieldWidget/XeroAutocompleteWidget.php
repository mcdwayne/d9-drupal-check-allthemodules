<?php

namespace Drupal\xero\Plugin\Field\FieldWidget;

use Drupal\xero\Plugin\Field\FieldType\XeroReference;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Provides an autocomplete textfield to look up a record on Xero by label or
 * GUID.
 *
 * @FieldWidget(
 *   id = "xero_autocomplete",
 *   label = @Translation("Xero autocomplete"),
 *   field_types = {
 *     "xero_reference"
 *   }
 * )
 *
 * @internal
 */
class XeroAutocompleteWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'xero_type' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    // It is not possible to implement ContainerInjectionInterface as a
    // FieldWidget plugin because WidgetBase implements __construct. DrupalWTF.
    $this->typedDataManager = \Drupal::typedDataManager();
  }

  /**
   * Get the Xero data type definition.
   *
   * @param $type
   *   The Xero type setting provided by this widget.
   * @return \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The Xero data type definition or FALSE.
   */
  protected function getXeroDefinition($type) {
    $types = XeroReference::getTypes();

    if (!in_array($type, $types)) {
      return FALSE;
    }

    try {
      $definition = $this->typedDataManager->getDefinition($type);
    }
    catch (PluginNotFoundException $e) {
      $definition = FALSE;
    }

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = array();
    $label = t('Not set');
    $type_options = array();

    $type_name = $this->getSetting('xero_type');
    $definition = $this->getXeroDefinition($type_name);

    if ($definition) {
      $label = $definition['label'];
    }

    $settings[] = t('Xero type: @name', array('@name' => $label));
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = $this->getTypeOptions();

    $form['xero_type'] = array(
      '#type' => 'select',
      '#title' => t('Xero Type'),
      '#description' => t('Select the Xero data type to use for this form.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('xero_type'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element += array(
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'xero.autocomplete',
      '#autocomplete_route_parameters' => array(
        'type' => $this->getSetting('xero_type'),
      ),
      '#default_value' => isset($items[$delta]->guid) ? $items[$delta]->guid . ' (' . $items[$delta]->label . ')' : '',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The item values keyed by field name.
    $return_values = array();
    $xero_type = $this->getSetting('xero_type');

    foreach ($values as $value) {
      $item = array(
        'type' => $xero_type,
      );

      preg_match('/([a-zA-Z0-9\-]+)(\s\((.+)\))?/', $value, $matches);
      $item['guid'] = isset($matches[1]) ? $matches[1] : '';
      $item['label'] = isset($matches[3]) ? $matches[3] : '';

      $return_values[] = $item;
    }

    return $return_values;
  }

  /**
   * Get the xero type options.
   *
   * @return array
   *   An array of options for a select list.
   */
  protected function getTypeOptions() {
    $options = array();

    $types = XeroReference::getTypes();

    foreach ($types as $type_name) {
      $definition = $this->getXeroDefinition($type_name);

      if ($definition) {
        $options[$type_name] = $definition['label'];
      }
    }

    return $options;
  }

}
