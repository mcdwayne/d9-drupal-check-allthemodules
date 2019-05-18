<?php

namespace Drupal\entity_reference_layout\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Entity Reference Layout Revisioned field type.
 *
 * @FieldType(
 *   id = "entity_reference_layout_revisioned",
 *   label = @Translation("Entity Reference with Layout"),
 *   description = @Translation("An entity field with layouts containing revisioned entity references."),
 *   category = @Translation("Reference revisions"),
 *   default_widget = "entity_reference_layout_widget",
 *   default_formatter = "entity_reference_layout",
 *   list_class = "\Drupal\entity_reference_layout\EntityReferenceLayoutRevisionsFieldItemList",
 * )
 */
class EntityReferenceLayoutRevisioned extends EntityReferenceRevisionsItem {

  /**
   * Define field properties.
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['region'] = DataDefinition::create('string')
      ->setLabel(t('Region'));

    $properties['layout'] = DataDefinition::create('string')
      ->setLabel(t('Layout'));

    $properties['section_id'] = DataDefinition::create('integer')
      ->setLabel(t('Section ID'));

    $properties['options'] = DataDefinition::create('any')
      ->setLabel(t('Options'));

    $properties['config'] = DataDefinition::create('any')
      ->setLabel(t('Config'));

    return $properties;
  }

  /**
   * Define field schema.
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['region'] = [
      'type' => 'varchar',
      'length' => '60',
      'not null' => FALSE,
    ];

    $schema['columns']['layout'] = [
      'type' => 'varchar',
      'length' => '60',
      'not null' => FALSE,
    ];

    $schema['columns']['section_id'] = [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => FALSE,
    ];

    $schema['columns']['options'] = [
      'type' => 'blob',
      'size' => 'normal',
      'serialize' => TRUE,
      'not null' => FALSE,
    ];

    $schema['columns']['config'] = [
      'type' => 'blob',
      'size' => 'normal',
      'serialize' => TRUE,
      'not null' => FALSE,
    ];
    return $schema;
  }

  /**
   * Manipulate field data to be saved as configuration.
   */
  public static function fieldSettingsToConfigData(array $settings) {
    $settings = parent::fieldSettingsToConfigData($settings);
    $allowed_layouts = [];
    $selected_layouts = isset($settings['handler_settings']['allowed_layouts']) ? $settings['handler_settings']['allowed_layouts'] : [];

    $layout_groups = \Drupal::service('plugin.manager.core.layout')->getLayoutOptions();
    foreach ($layout_groups as $group => $layouts) {
      foreach ($layouts as $name => $value) {
        if (!empty($selected_layouts[$group][$name])) {
          $allowed_layouts[$group][$name] = $value;
        }
      }
    }

    $settings['handler_settings']['allowed_layouts'] = $allowed_layouts;
    return $settings;
  }

  /**
   * Manipulate saved data into configuration.
   *
   * Set 'layout_bundles' configuration item as an array even though it
   * may be stored as a string in case we support attaching layouts to
   * multiple bundles in the future.
   */
  public static function fieldSettingsFromConfigData(array $settings) {
    if (isset($settings['handler_settings']['layout_bundles']) && !is_array($settings['handler_settings']['layout_bundles'])) {
      $settings['handler_settings']['layout_bundles'] = [$settings['handler_settings']['layout_bundles']];
    }
    return $settings;
  }

  /**
   * Manipulate configuration data for settings form.
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $form = parent::fieldSettingsForm($form, $form_state);
    $handler_settings = $this->getSetting('handler_settings');
    $target_type = $this->getSetting('target_type');

    $negate = isset($handler_settings['negate']) ? $handler_settings['negate'] : 0;
    $input_negate = $form_state->getValue([
      'settings',
      'handler_settings',
      'negate',
    ]);
    if (isset($input_negate)) {
      $negate = $input_negate;
    }
    $form['handler']['handler_settings']['negate']['#ajax'] = TRUE;

    $target_bundles = isset($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : [];
    // For AJAX, also look at form_state values:
    $input_target_bundles = $form_state->getValue([
      'settings',
      'handler_settings',
      'target_bundles',
    ]);
    if (!empty($input_target_bundles)) {
      $target_bundles = $input_target_bundles;
    }
    $target_bundle_options = $form['handler']['handler_settings']['target_bundles']['#options'];

    if ($negate) {
      $layout_bundle_options = array_diff_key($target_bundle_options, $target_bundles);
    }
    else {
      $layout_bundle_options = array_intersect_key($target_bundle_options, $target_bundles);
    }
    if (!empty($form['handler']['handler_settings']['target_bundles_drag_drop'])) {
      foreach (Element::children($form['handler']['handler_settings']['target_bundles_drag_drop']) as $item) {
        $form['handler']['handler_settings']['target_bundles_drag_drop'][$item]['enabled']['#ajax'] = TRUE;
      }
    }

    $form['handler']['handler_settings']['layout_bundles'] = [
      '#type' => 'radios',
      '#options' => $layout_bundle_options,
      '#title' => $this->t('Layout @target_type type', ['@target_type' => $target_type]),
      '#default_value' => isset($handler_settings['layout_bundles']) ? reset($handler_settings['layout_bundles']) : [],
      '#multiple' => TRUE,
      '#description' => $this->t('Which @target_type type should be used for layout.', ['@target_type' => $target_type]),
      '#required' => TRUE,
      '#id' => 'erl-layout-bundles-select',
    ];

    $layout_groups = \Drupal::service('plugin.manager.core.layout')->getLayoutOptions();
    $layout_groups_defaults = isset($handler_settings['allowed_layouts']) ? $handler_settings['allowed_layouts'] : [];

    foreach ($layout_groups as $group => $layouts) {
      $defaults = isset($layout_groups_defaults[$group]) ? array_keys($layout_groups_defaults[$group]) : [];
      $form['handler']['handler_settings']['allowed_layouts'][$group] = [
        '#type' => 'checkboxes',
        '#options' => $layouts,
        '#title' => $group,
        '#multiple' => TRUE,
        '#default_value' => $defaults,
      ];
    }
    $form['handler']['handler_settings']['allowed_layouts']['#prefix'] = '<b>' . t('Allowed Layouts:') . '</b>';
    return $form;
  }

  /**
   * Get defaults settings.
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['handler_settings']['layout_bundles'] = [];
    $settings['handler_settings']['allowed_layouts'] = [];
    return $settings;
  }

  /**
   * {@inheritdoc}
   *
   * Only support references to paragraphs.
   *
   * @todo Expand support to other entity types.
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);
    foreach ($element['target_type']['#options'] as $key => $value) {
      if ($key !== 'paragraph') {
        unset($element['target_type']['#options'][$key]);
      }
    }
    return $element;
  }

}
