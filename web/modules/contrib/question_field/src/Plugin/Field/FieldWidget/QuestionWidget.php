<?php

namespace Drupal\question_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\question_field\Plugin\Field\FieldType\QuestionItem;

/**
 * Plugin implementation of the 'question_widget' widget.
 *
 * @FieldWidget(
 *   id = "question_widget",
 *   module = "question_field",
 *   label = @Translation("Question widget."),
 *   field_types = {
 *     "question"
 *   }
 * )
 */
class QuestionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\question_field\Plugin\Field\FieldType\QuestionItem $item */
    $item = $items[$delta];
    $values = $item->getValue();

    $element['question'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Question'),
      '#description' => $this->t('Enter the question to ask.'),
      '#default_value' => $values ? $values['question'] : '',
      '#size' => 50,
      '#maxlength' => 1024,
    ];

    $element['answer_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Question type'),
      '#description' => $this->t('Select the type of question being asked. If the question is a multiple choice question, you will be able to enter the available answer choices.'),
      '#default_value' => $values ? $values['answer_type'] : 'textfield',
      '#options' => QuestionItem::allowedValues(),
      '#chosen' => FALSE,
    ];

    // Get the full answer_type field name to use in answer_options #states.
    $name = $item->getFieldDefinition()->getName();
    if ($form_state->getFormObject()->getFormId() == 'field_config_edit_form') {
      $name = 'default_value_input[' . $name . ']';
    }
    $name .= '[' . $delta . '][answer_type]';

    // Create the #states array to hide answer_options when they are not used.
    $selector = ':input[name="' . $name . '"]';
    $states = [];
    foreach (QuestionItem::disallowedOptions() as $option) {
      $states[] = [$selector => ['value' => $option]];
    }

    // Add the answer options with #states to hide when it isn't used.
    $element['answer_options'] = [
      '#title' => $this->t('Answer choices'),
      '#description' => $this->t('Enter each answer on a separate line with the value|text separated with a |.'),
      '#type' => 'textarea',
      '#default_value' => $values ? $values['answer_options'] : [],
      '#states' => ['invisible' => $states],
    ];

    $element['answer_default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default answer'),
      '#description' => $this->t('The default answer.'),
      '#default_value' => $values ? $values['answer_default'] : '',
      '#size' => 50,
    ];

    $element['answer_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Answer required'),
      '#description' => $this->t('Check if the answer to this question must be provided; for the "checkbox" type this means that the checkbox must be checked.'),
      '#default_value' => $values ? $values['answer_required'] : FALSE,
    ];

    return $element;
  }

}
