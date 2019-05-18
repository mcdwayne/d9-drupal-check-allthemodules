<?php

/**
 * @file
 * Contains Drupal\prism\Plugin\Field\FieldType\TextPrismItem.
 */

namespace Drupal\prism\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;

/**
 * Plugin implementation of the 'text_long_prism' field type.
 *
 * @FieldType(
 *   id = "text_long_prism",
 *   label = @Translation("Text (prism)"),
 *   description = @Translation("This field stores long text in the database."),
 *   category = @Translation("Text"),
 *   default_widget = "text_prism",
 *   default_formatter = "prism_default"
 * )
 */
class TextPrismItem extends TextItemBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Prevent early t() calls by using the TranslationWrapper.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslationWrapper('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    $properties['languages'] = DataDefinition::create('string')
      ->setLabel(new TranslationWrapper('Language'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => $field_definition->getSetting('case_sensitive') ? 'blob' : 'text',
          'size' => 'big',
        ),
        'languages' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
