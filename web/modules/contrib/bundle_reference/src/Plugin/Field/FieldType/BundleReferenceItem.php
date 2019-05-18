<?php

namespace Drupal\bundle_reference\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;

/**
 * Plugin implementation of the 'bundle_reference' field type.
 *
 * @FieldType(
 *   id = "bundle_reference",
 *   label = @Translation("Bundle reference"),
 *   category = @Translation("Reference"),
 *   module = "bundle_reference",
 *   description = @Translation("Allows to reference a bundle of any entity type."),
 *   default_widget = "bundle_reference_widget",
 *   default_formatter = "bundle_reference_formatter"
 * )
 */
class BundleReferenceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'entity_type' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'bundle' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'referencable_bundles' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * Helper function to get the list of referencable bundles.
   *
   * @return array
   *   Array of bundle options filtered with the allowed array.
   */
  protected function getBundleOptions() {
    $bundle_options = [];
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
    foreach ($bundle_info as $entity_type_id => $bundle_data) {
      $entityType = \Drupal::service('entity_type.manager')->getDefinition($entity_type_id);
      if ($entityType instanceof ContentEntityTypeInterface) {
        foreach ($bundle_data as $bundle_id => $data) {
          $identifier = $entity_type_id . ':' . $bundle_id;
          $bundle_options[$identifier] = $entityType->getLabel() . ': ' . $data['label'];
        }
      }
    }
    return $bundle_options;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();
    $element['referencable_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Referencable bundles'),
      '#options' => $this->getBundleOptions(),
      '#default_value' => $settings['referencable_bundles'],
      '#description' => t('Limit referencable bundles to the selected values.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $bundle = $this->get('bundle')->getValue();
    return empty($bundle);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['entity_type'] = DataDefinition::create('string')
      ->setLabel(t('Entity type ID'));
    $properties['bundle'] = DataDefinition::create('string')
      ->setLabel(t('Bundle ID'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSettingsToConfigData(array $settings) {
    foreach ($settings['referencable_bundles'] as $key => $value) {
      if (empty($value)) {
        unset($settings['referencable_bundles'][$key]);
      }
    }
    return $settings;
  }

}
