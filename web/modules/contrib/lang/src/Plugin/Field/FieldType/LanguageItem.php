<?php

/**
 * @file
 * Contains \Drupal\lang\Plugin\FieldType\LanguageItem.
 */

namespace Drupal\lang\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'language' field type.
 *
 * @FieldType(
 *   id = "lang",
 *   label = @Translation("Simple Language"),
 *   description = @Translation("Stores the langcode for a language."),
 *   default_widget = "language_default",
 *   default_formatter = "language_default"
 * )
 */

class LanguageItem extends FieldItemBase {

  const LANGUAGE_CODE_MAXLENGTH = 20;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Language'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'char',
          'length' => static::LANGUAGE_CODE_MAXLENGTH,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $constraints[] = $constraint_manager->create('ComplexData', array(
      'value' => array(
        'Length' => array(
          'max' => static::LANGUAGE_CODE_MAXLENGTH,
          'maxMessage' => t('%name: the language code may not be longer than @max characters.', array('%name' => $this->getFieldDefinition()->getLabel(), '@max' => static::LANGUAGE_CODE_MAXLENGTH)),
        )
      ),
    ));

    return $constraints;
  }

}
