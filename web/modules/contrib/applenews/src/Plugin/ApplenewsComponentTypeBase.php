<?php

namespace Drupal\applenews\Plugin;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldStorageConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class from which other Apple News component types may extend.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_applenews_component_type_plugin_info_alter().
 * The definition includes the following keys:
 * - id: The unique, system-wide identifier of the component type.
 * - label: The human-readable name of the component type, translated.
 * - description: A human-readable description for the component type,
 *   translated.
 * - component_type: A schema-defined "meta-type" that describes the type of
 *   data the component will display. Options: text, image, nested, or divider.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @ApplenewsComponentType(
 *  id = "your_component_id",
 *  label = @Translation("Your component label"),
 *  description = @Translation("Your component description"),
 *  component_type = "image",
 * )
 * @endcode
 */
abstract class ApplenewsComponentTypeBase extends PluginBase implements ApplenewsComponentTypeInterface {

  /**
   * Field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['component_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Adding @component_name component', ['@component_name' => $this->label()]),
      '#tree' => TRUE,
    ];

    $element['component_settings']['component_layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Component Layout'),
      '#open' => TRUE,
    ];

    $element['component_settings']['component_layout']['column_start'] = [
      '#type' => 'number',
      '#title' => $this->t('Column Start'),
      '#description' => $this->t("Indicates which column the component's start position is in, based on the number of columns in the document or parent container. By default, the component will start in the first column (note that the first column is 0, not 1)."),
      '#default_value' => 0,
    ];

    $element['component_settings']['component_layout']['column_span'] = [
      '#type' => 'number',
      '#title' => $this->t('Column Span'),
      '#description' => $this->t("Indicates how many columns the component spans, based on the number of columns in the document. By default, the component spans the entire width of the document or the width of its container component."),
      '#default_value' => 7,
    ];

    $element['component_settings']['component_layout']['margin_top'] = [
      '#type' => 'number',
      '#title' => $this->t('Margin Top'),
      '#description' => $this->t('The margin for the top of this component.'),
      '#default_value' => 0,
    ];

    $element['component_settings']['component_layout']['margin_bottom'] = [
      '#type' => 'number',
      '#title' => $this->t('Margin Bottom'),
      '#description' => $this->t('The margin for the bottom of this component.'),
      '#default_value' => 0,
    ];

    $element['component_settings']['component_layout']['ignore_margin'] = [
      '#type' => 'select',
      '#title' => $this->t('Ignore Document Margin'),
      '#description' => $this->t("Indicates whether a document's margins should be respected or ignored by the parent container."),
      '#options' => [
        'none' => $this->t('None'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
        'both' => $this->t('Both'),
      ],
    ];

    $element['component_settings']['component_layout']['ignore_gutter'] = [
      '#type' => 'select',
      '#title' => $this->t('Ignore Document Gutter'),
      '#description' => $this->t('Indicates whether the gutters (if any) to the left and right of the component should be ignored.'),
      '#options' => [
        'none' => $this->t('None'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
        'both' => $this->t('Both'),
      ],
    ];

    $element['component_settings']['component_layout']['minimum_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Height'),
      '#description' => $this->t('Sets the minimum height of the component.'),
      '#default_value' => 10,
    ];

    $element['component_settings']['component_layout']['minimum_height_unit'] = [
      '#type' => 'select',
      '#options' => $this->getUnitsOfMeasure(),
      '#description' => $this->t('Available units of measure for minimum height. See <a href="@link">Apple News unit documentation</a>.', ['@link' => 'https://developer.apple.com/library/content/documentation/General/Conceptual/Apple_News_Format_Ref/Layout.html#//apple_ref/doc/uid/TP40015408-CH65-SW1']),
    ];

    $element['component_settings']['id'] = [
      '#type' => 'hidden',
      '#value' => $this->pluginId,
    ];

    $element['component_settings']['component_data'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Component Data'),
      '#prefix' => '<div id="component-field-mapping-properties-wrapper">',
      '#suffix' => '</div>',
    ];

    // @todo add more component layout form elements

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentClass() {
    return $this->pluginDefinition['component_class'];
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentType() {
    return $this->pluginDefinition['component_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $field_manager) {
    $this->fieldManager = $field_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager')
    );
  }

  /**
   * Get all of the fields of a node type as options for a form.
   *
   * @param string $node_type
   *   String node type.
   *
   * @return array
   *   An array of options suitable for a Form API selection element.
   */
  protected function getFieldOptions($node_type) {
    $fields = $this->fieldManager->getFieldDefinitions('node', $node_type);
    $field_options = [];
    $available_base_fields = $this->getBaseFields();
    foreach ($fields as $field_name => $field) {
      if ($field->getFieldStorageDefinition()->isBaseField() && in_array($field_name, $available_base_fields)) {
        $field_options[$field_name] = $field->getLabel();
      }
      elseif (!$field->getFieldStorageDefinition()->isBaseField()) {
        $field_options[$field_name] = $field->getLabel() . ' (' . $field->getType() . ')';
      }
    }

    return $field_options;
  }

  /**
   * Get field machine names of base fields.
   *
   * @return array
   *   An array of base fields.
   */
  protected function getBaseFields() {
    return [
      'title',
      'created',
      'changed',
    ];
  }

  /**
   * Provides select element.
   *
   * Get a field selection element that will have all fields on the selected
   * content type as an option, and allow dynamic selection of their properties.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param string $name
   *   String name.
   * @param string $label
   *   String label.
   *
   * @return array
   *   A Form API render array
   */
  protected function getFieldSelectionElement(FormStateInterface $form_state, $name, $label) {
    $input = $form_state->getUserInput();
    $node_type = $input['node_type'];

    $field_options = $this->getFieldOptions($node_type);
    $default_field = current(array_keys($field_options));

    $triggering_element = $form_state->getTriggeringElement();
    $field_selection_name = 'component_settings[component_data][' . $name . '][field_name]';
    if (isset($triggering_element) && $triggering_element['#name'] == $field_selection_name) {
      $default_field = $triggering_element['#value'];
    }

    if (isset($input['component_settings']['component_data'][$name]['field_name'])) {
      $default_field = $input['component_settings']['component_data'][$name]['field_name'];
    }

    $default_field_config = FieldConfig::loadByName('node', $node_type, $default_field);

    $element['field_name'] = [
      '#type' => 'select',
      '#title' => $this->t($label),
      '#options' => $this->getFieldOptions($node_type),
      '#ajax' => [
        'callback' => [$this, 'ajaxGetFieldPropertySelectionElement'],
        'wrapper' => 'component-field-mapping-properties-wrapper',
      ],
      '#default_value' => $default_field,
    ];

    if ($default_field_config && !$default_field_config->getFieldStorageDefinition()->isBaseField()) {
      $element['field_property'] = $this->getFieldPropertySelectionElement($default_field_config->getFieldStorageDefinition());;
    }
    else {
      // Base fields do not have properties, so set a value we can check for.
      $element['field_property'] = [
        '#type' => 'hidden',
        '#value' => 'base',
      ];
    }

    return $element;
  }

  /**
   * Ajax callback for the field name selection element.
   */
  public function ajaxGetFieldPropertySelectionElement(array &$form, FormStateInterface $form_state) {
    return $form['add_components']['component_settings']['component_data'];
  }

  /**
   * Get all the properties of a field as a selection element.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $config
   *   Field storage config.
   *
   * @return array
   *   A Form API render array
   */
  protected function getFieldPropertySelectionElement(FieldStorageConfigInterface $config) {
    $field_name = $config->getName();
    $properties = $config->getPropertyDefinitions();

    $property_options = [];
    foreach ($properties as $property => $definition) {
      $property_options[$property] = $definition->getLabel();
    }

    return [
      '#type' => 'select',
      '#title' => $this->t($field_name . ' Property'),
      '#options' => $property_options,
    ];
  }

  /**
   * Get the options for Units of Measure.
   *
   * @see https://developer.apple.com/library/content/documentation/General/Conceptual/Apple_News_Format_Ref/SupportedUnits.html#//apple_ref/doc/uid/TP40015408-CH75-SW1
   *
   * @return array
   *   An array of options suitable for a Form API selection element.
   */
  protected function getUnitsOfMeasure() {
    return [
      'pt' => $this->t('Points'),
      'vh' => $this->t('Viewport Height'),
      'vw' => $this->t('Viewport Width'),
      'vmin' => $this->t('Viewport Shortest Side'),
      'vmax' => $this->t('Viewport Longest Side'),
      'dg' => $this->t('Column Gutters'),
      'dm' => $this->t('Document Margin'),
      'cw' => $this->t('Component Width'),
    ];
  }

  /**
   * Provides element width.
   *
   * Get the form fields necessary for setting the maximum content width for a
   * component. Only a few component types recognize this setting.
   *
   * @return array
   *   A Form API render array
   */
  protected function getMaximumContentWidthElement() {
    $element = [];

    $element['maximum_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Content Width'),
      '#description' => $this->t('Sets the maximum width of the content within the component.'),
    ];

    $element['maximum_width_unit'] = [
      '#type' => 'select',
      '#options' => $this->getUnitsOfMeasure(),
      '#description' => $this->t('Available units of measure for maximum width. See <a href="@link">Apple News unit documentation</a>.', ['@link' => 'https://developer.apple.com/library/content/documentation/General/Conceptual/Apple_News_Format_Ref/Layout.html#//apple_ref/doc/uid/TP40015408-CH65-SW1']),
    ];

    return $element;
  }

}
