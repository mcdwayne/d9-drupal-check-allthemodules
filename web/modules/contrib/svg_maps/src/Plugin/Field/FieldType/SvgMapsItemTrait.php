<?php

namespace Drupal\svg_maps\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides some methods used to svg maps items.
 */
trait SvgMapsItemTrait {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'svg_maps_plugin' => 'generic',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      'svg_maps_item' => DataDefinition::create('string')
        ->setLabel(t('Map Element'))
        ->setDescription(t('The map element'))
        ->setRequired(TRUE),
    ] + parent::propertyDefinitions($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['svg_maps_item'] = [
      'type' => 'varchar',
      'length' => 255,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $settings = $this->getSettings();

    $plugin_manager = \Drupal::service('plugin.manager.svg_maps.type');
    $plugin_definitions = $plugin_manager->getDefinitions();

    $options = [];
    foreach ($plugin_definitions as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'];
    }

    $elements['svg_maps_plugin'] = [
      '#type' => 'select',
      '#title' => t('Svg Maps Type'),
      '#options' => $options,
      '#default_value' => $settings['svg_maps_plugin'],
      '#description' => t('The type of map who will be render.'),
      '#required' => TRUE,
    ];

    return $elements + parent::fieldSettingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $svg_maps_item_value = $this->get('svg_maps_item')->getValue();
    return empty($svg_maps_item_value) || parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);

    $settings = $field_definition->getSettings();
    $svg_map_bundle = $settings['svg_maps_plugin'];

    $config = \Drupal::configFactory()->listAll('svg_maps.svg_maps_entity');
//    $values['svg_maps_item'] = array_rand($results);

    return $values;
  }

}
