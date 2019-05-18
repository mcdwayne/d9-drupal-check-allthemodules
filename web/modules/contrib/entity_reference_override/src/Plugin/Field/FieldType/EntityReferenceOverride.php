<?php

namespace Drupal\entity_reference_override\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_override' field type.
 *
 * @FieldType(
 *   id = "entity_reference_override",
 *   label = @Translation("Entity reference w/custom text"),
 *   description = @Translation("Entity reference with custom text"),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_override_autocomplete",
 *   default_formatter = "entity_reference_override_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList" * )
 */
class EntityReferenceOverride extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $override_definition = DataDefinition::create('string')
      ->setLabel($field_definition->getSetting('override_label'))
      ->setRequired(FALSE);
    $properties['override'] = $override_definition;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['override'] = array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    );
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'override_label' => t('Custom text'),
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::fieldSettingsForm($form, $form_state);

    $elements['override_label'] = [
      '#type' => 'textfield',
      '#title' => t('Custom text label'),
      '#default_value' => $this->getSetting('override_label'),
      '#description' => t('Also used as a placeholder in multi-value instances.')
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    // In the base EntityReference class, this is used to populate the
    // list of field-types with options for each destination entity type.
    // Too much work, we'll just make people fill that out later.
    // Also, keeps the field type dropdown from getting too cluttered.
    return array();
  }
}