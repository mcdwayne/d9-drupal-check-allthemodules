<?php

/**
 * @file
 * Contains \Drupal\content_callback\Plugin\field\field_type\ContentCallbackItem.
 */

namespace Drupal\content_callback\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'content callback' field type.
 *
 * @FieldType(
 *   id = "content_callback",
 *   label = @Translation("Content callback"),
 *   description = @Translation("This field show the output of a content callback."),
 *   default_widget = "content_callback_select",
 *   default_formatter = "content_callback_default"
 * )
 */
class ContentCallbackItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 256,
          'not null' => FALSE,
        ),
        'options' => array(
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $value = $this->get('value')->getValue();
    if (empty($value) && $value !== '0') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Defines field item properties.
   *
   * Properties that are required to constitute a valid, non-empty item should
   * be denoted with \Drupal\Core\TypedData\DataDefinition::setRequired().
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   An array of property definitions of contained properties, keyed by
   *   property name.
   *
   * @see \Drupal\Core\Field\BaseFieldDefinition
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Selected callback'));

    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Options'));

    return $properties;
  }

}
