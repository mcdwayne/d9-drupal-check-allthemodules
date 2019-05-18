<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\Field\FieldType\CollectDataItem.
 */

namespace Drupal\collect\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'collect_data' entity field type.
 *
 * @FieldType(
 *   id = "collect_data",
 *   label = @Translation("Data"),
 *   description = @Translation("An entity field for storing binary values with a mime type describing the type of stored data."),
 *   no_ui = TRUE
 * )
 *
 * @todo Add file reference property and handle data and file reference
 *   transparently.
 *   This allows to deal with big and small while keeping performance for small
 *   ones.
 */
class CollectDataItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = array();

    $properties['data'] = DataDefinition::create('any')
      ->setLabel(t('Data value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'data' => array(
          'type' => 'blob',
          'size' => 'big',
        ),
      ),
    );
  }
}
