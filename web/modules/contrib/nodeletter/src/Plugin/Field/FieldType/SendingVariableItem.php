<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Plugin\Field\FieldType\SendingVariableItem.
 */

namespace  Drupal\nodeletter\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a field type for storing rendered template variables of a nodeletter
 * sending.
 *
 * @FieldType(
 *   id = "nodeletter_sending_variable",
 *   label = @Translation("Sending variable"),
 *   description = @Translation("Field to store variables of nodeletter sendings."),
 *   default_formatter = "nodeletter_sending_variable_default",
 * )
 */
class SendingVariableItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'name' => array(
          'description' => 'Variable name',
          'type' => 'text',
          'size' => 'tiny',
          'not null' => TRUE,
        ),
        'value' => array(
          'description' => 'Variable value (may contain markup)',
          'type' => 'text',
          'size' => 'medium',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['name'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name', [], ['context' => 'Variable name']));
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Value', [], ['context' => 'Variable value']));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $name = $this->get('name')->getValue();
    return ($name === NULL || $name === '') && parent::isEmpty();
  }

}
