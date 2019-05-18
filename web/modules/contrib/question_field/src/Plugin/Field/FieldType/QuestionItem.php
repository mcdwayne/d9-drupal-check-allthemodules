<?php

namespace Drupal\question_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\question_field\AnswerOptions;

/**
 * Plugin implementation of the 'field_example_rgb' field type.
 *
 * @FieldType(
 *   id = "question",
 *   label = @Translation("Question"),
 *   module = "question_field",
 *   description = @Translation("Question and answer field"),
 *   default_widget = "question_widget",
 *   default_formatter = "question_form_formatter"
 * )
 */
class QuestionItem extends FieldItemBase {

  const QUESTION_LENGTH = 255;
  const DESCRIPTION_LENGTH = 1024;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'question' => [
          'type' => 'varchar',
          'length' => self::QUESTION_LENGTH,
        ],
        'question_description' => [
          'type' => 'varchar',
          'length' => self::DESCRIPTION_LENGTH,
        ],
        'answer_type' => [
          'type' => 'varchar',
          'length' => 16,
        ],
        'answer_default' => [
          'type' => 'varchar',
          'length' => 256,
        ],
        'answer_options' => [
          'type' => 'text',
        ],
        'answer_required' => [
          'type' => 'int',
          'size' => 'tiny',
        ]
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $question = $this->get('question')->getValue();
    return empty($question);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['question'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Question'))
      ->setRequired(TRUE);
    $properties['question_description'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Question'));
    $properties['answer_type'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Answer type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::allowedValues());
    $properties['answer_default'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Answer default value'));
    $properties['answer_options'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Answer options'))
      ->addConstraint('AnswerOptions');
    $properties['answer_required'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Answer Required'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * Get the allowed value options.
   *
   * @return array
   *   Array of allowed values mapped to their translated display name.
   */
  public static function allowedValues() {
    $allowed_values = [
      'checkbox' => t('Checkbox'),
      'checkboxes' => t('Checkboxes'),
      'radios' => t('Radio'),
      'select' => t('Select'),
      'number' => t('Number'),
      'textfield' => t('Text'),
      'tel' => t('Phone'),
    ];
    if (\Drupal::moduleHandler()->moduleExists('address')) {
      $allowed_values['address'] = t('Address');
    }
    return $allowed_values;
  }

  /**
   * Return of array of field types that use the answer_options.
   *
   * @return array
   *   Array of field types that use the answer_options.
   */
  public static function allowedOptions() {
    return ['checkboxes', 'radios', 'select'];
  }

  /**
   * Return of array of field types that do NOT use the answer_options.
   *
   * @return array
   *   Array of field types that do NOT use the answer_options.
   */
  public static function disallowedOptions() {
    return array_diff(array_keys(self::allowedValues()), self::allowedOptions());
  }

  /**
   * Return the question.
   *
   * @return string
   *   The question.
   */
  public function getQuestion() {
    return $this->get('question')->getValue();
  }

  /**
   * Return the question description.
   *
   * @return string
   *   The question description.
   */
  public function getQuestionDescription() {
    return $this->get('question_description')->getValue();
  }

  /**
   * Return the answer's default value.
   *
   * @return string
   *   The answer's default value.
   */
  public function getAnswerDefault() {
    return $this->get('answer_default')->getValue();
  }

  /**
   * Return the answer type.
   *
   * @return string
   *   The answer type.
   */
  public function getAnswerType() {
    return $this->get('answer_type')->getValue();
  }

  /**
   * Return if the answer is required.
   *
   * @return boolean
   *   TRUE if the answer is required; FALSE otherwise.
   */
  public function getAnswerRequired() {
    return $this->get('answer_required')->getValue() ? TRUE : FALSE;
  }

  /**
   * Return array of answer options or the [] if options are not supported.
   *
   * @return \Drupal\question_field\AnswerOptions[]
   *   Array of answer options.
   */
  public function getAnswerOptions() {
    // Return immediately on disallowed answer types.
    if (!in_array($this->getAnswerType(), self::allowedOptions())) {
      return [];
    }

    // Get the options.
    $answer_options = $this->get('answer_options')->getValue();
    $options = [];
    $answers = explode("\n", $answer_options);
    foreach ($answers as $answer) {
      $answer = trim($answer);
      if ($answer) {
        $options[] = new AnswerOptions($answer);
      }
    }
    return $options;
  }

  /**
   * Return a unique field id for the question item.
   *
   * @return string
   *   A unique field id.
   */
  public function uniqueId() {
    $values = array_filter($this->getValue(), function ($value) {
      return !is_array($value);
    });
    return md5(implode(',', $values));
  }

}
