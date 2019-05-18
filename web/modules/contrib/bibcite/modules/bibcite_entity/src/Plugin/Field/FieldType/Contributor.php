<?php

namespace Drupal\bibcite_entity\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'bibcite_contributor' field type.
 *
 * @FieldType(
 *   id = "bibcite_contributor",
 *   label = @Translation("Contributor"),
 *   no_ui = TRUE,
 *   description = @Translation("Entity reference with label"),
 *   default_widget = "bibcite_contributor_widget",
 *   default_formatter = "bibcite_contributor_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class Contributor extends EntityReferenceItem implements ContributorFieldInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'bibcite_contributor',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'handler' => 'default:bibcite_contributor',
      'handler_settings' => [
        'auto_create' => TRUE,
        'auto_create_bundle' => 'bibcite_contributor',
      ],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['category'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Category'))
      ->setSetting('case_sensitive', TRUE);

    $properties['role'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Role'))
      ->setSetting('case_sensitive', TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'target_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['category'] = [
      'type' => 'varchar',
      'length' => 255,
    ];
    $schema['columns']['role'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

}
