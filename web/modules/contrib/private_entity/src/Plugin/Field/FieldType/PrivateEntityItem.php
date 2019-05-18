<?php

namespace Drupal\private_entity\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'private_entity' field type.
 *
 * @todo on validation, there can only be one configured field per entity/bundle
 *
 * @FieldType(
 *   id = "private_entity",
 *   label = @Translation("Private entity"),
 *   description = @Translation("Private entity field storage."),
 *   default_widget = "private_entity_default_widget",
 *   default_formatter = "private_entity_default_formatter"
 * )
 */
class PrivateEntityItem extends FieldItemBase {

  const ACCESS_PUBLIC = 0;
  const ACCESS_PRIVATE = 1;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Private entity'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          // Integer has been preferred to boolean to allow further extension
          // without altering the schema.
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'sortable' => TRUE,
          'views' => TRUE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    // @todo check is empty
    return $value === NULL || $value === self::ACCESS_PUBLIC;
  }

}
