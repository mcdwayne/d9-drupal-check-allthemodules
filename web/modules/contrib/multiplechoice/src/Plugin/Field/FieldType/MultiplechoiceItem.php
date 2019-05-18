<?php

namespace Drupal\multiplechoice\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\multiplechoice\Plugin\MultiplechoiceSettingsTrait;
//use Drupal\Core\TypedData\MapDataDefinition;
//use Drupal\Core\Url;
//use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'multiple choice' field type.
 *
 * @FieldType(
 *   id = "multiplechoice",
 *   label = @Translation("Multiple choice"),
 *   description = @Translation("Create a multiple choice field."),
 *   default_widget = "multiplechoice_answers",
 *   default_formatter = "multiplechoice"
 * )
 */
class MultiplechoiceItem extends FieldItemBase implements FieldItemInterface {

  use MultiplechoiceSettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'takes' => 2,
      'quiz_open' => time(),
      'quiz_close' => time(),
      'pass_rate' => 75,
      'backwards_navigation' => 0
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['question'] = DataDefinition::create('string')
      ->setLabel(t('Question'));

    $properties['format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'));

    $properties['question_processed'] = DataDefinition::create('string')
      ->setLabel(t('Question processed'))
      ->setDescription(t('The Question with the text format applied.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\text\TextProcessed')
      ->setSetting('text source', 'question');

    $properties['answers'] = DataDefinition::create('string')
      ->setLabel(t('Answers'));

    $properties['difficulty'] = DataDefinition::create('integer')
      ->setLabel(t('Difficulty'));

    $properties['correct_answer'] = DataDefinition::create('integer')
      ->setLabel(t('Correct Answer'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'question' => array(
          'description' => 'The question.',
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
        ),
        'format' => array(
          'type' => 'varchar_ascii',
          'length' => 255,
        ),
        'answers' => array(
          'description' => 'The serialized data for the answers.',
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
          'serialize' => TRUE,
        ),
        'difficulty' => array(
          'description' => "Difficulty rating.",
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'correct_answer' => array(
          'description' => "The correct answer to the question based on the delta of the question.",
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(
        'format' => array('format'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getSettings();

    return $this->multipleChoiceSettingsForm($settings);

  }

  /**
   * Builds the default_question details element.
   *
   * @param array $element
   *   The form associative array passed by reference.
   * @param array $settings
   *   The field settings array.
   */
  protected function defaultQuestionForm(array &$element, array $settings) {
    $element['default_question'] = array(
      '#type' => 'details',
      '#title' => t('Default question'),
      '#open' => TRUE,
    );
    // Convert the stored UUID to a FID.

    $element['default_question']['question'] = array(
      '#type' => 'text_format',
      '#title' => t('Question'),
      '#description' => t('.'),
      '#default_value' => $settings['default_question']['question']
    );
    $element['default_question']['answers'] = array(
      '#type' => 'textfield',
      '#title' => t('Answers'),
      '#description' => t('This text will be used by screen readers, search engines, and when the question cannot be loaded.'),
      '#default_value' => $settings['default_question']['answers'],
      '#maxlength' => 512,
    );
    $element['default_question']['difficulty'] = array(
      '#type' => 'textfield',
      '#title' => t('Difficulty'),
      '#description' => t('.'),
      '#default_value' => $settings['default_question']['difficulty'],
      '#maxlength' => 1024,
    );
    $element['default_question']['correct_answer'] = array(
      '#type' => 'textfield',
      '#title' => t('Correct Answer'),
      '#description' => t('Stores the correct answer.'),
      '#default_value' => $settings['default_question']['correct_answer'],
      '#maxlength' => 1024,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $question = $this->get('question')->getValue();
    if ($question == '') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'question';
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.

    if (isset($values['question']) && is_array($values['question'])) {
      $question = $values['question'];
      $values['question'] = $question['value'];
    }
    else {
      //$values['question']['value'] = $values['question'];
    }


    if (isset($values['container']) && is_array($values['container'])) {
      $values['answers'] = serialize($values['container']);
      foreach ($values['container'] as $delta => $value) {
        if (!is_array($value)) {
          continue;
        }
        if ($value['correct'] == 1) {
          $values['correct_answer'] = $delta;
          break;
        }
      }
    }
    elseif (isset($values['answers'])) {
      $values['container'] = unserialize($values['answers']);

    }

    // $values['format'] = $question['format'];
//    if (isset($values) && !is_array($values)) {
//      $values = [static::mainPropertyName() => $values];
//    }
//    if (isset($values)) {
//      $values += [
//        'options' => [],
//      ];
//    }
    // Unserialize the values.
    // @todo The storage controller should take care of this, see
    //   SqlContentEntityStorage::loadFieldItems, see
    //   https://www.drupal.org/node/2414835
//    if (is_string($values['options'])) {
//      $values['options'] = unserialize($values['options']);
//    }


    parent::setValue($values, $notify);
  }

}
