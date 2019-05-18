<?php

/**
 * @file
 * Contains Drupal\field_example\Plugin\Field\FieldType\RgbItem.
 */

namespace Drupal\field_quiz\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_example_rgb' field type.
 *
 * @FieldType(
 *   id = "field_quiz_question",
 *   label = @Translation("Field Quiz Question"),
 *   module = "field_quiz",
 *   description = @Translation("Demonstrates a field for multiple choice questions and answers."),
 *   default_widget = "field_quiz_question_widget",
 *   default_formatter = "field_quiz_question_formatter"
 * )
 */
class QuestionItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    return array(
      'columns' => array(
        'answer' => array(
          'type' => 'varchar',
          'length' => 1024,
          'not null' => FALSE,
        ),
        'correct' => array(
          'type' => 'int',
          'length' => 4,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $answer = $this->get('answer')->getValue();
    return $answer === NULL || $answer === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['answer'] = DataDefinition::create('string')
      ->setLabel(t('Quiz answer.'));
    $properties['correct'] = DataDefinition::create('string')
      ->setLabel(t('Correct answer?'));
    return $properties;
  }

}
