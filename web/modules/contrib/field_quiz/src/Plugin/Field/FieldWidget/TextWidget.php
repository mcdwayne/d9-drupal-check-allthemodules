<?php

/**
 * @file
 * Contains \Drupal\field_quiz\Plugin\field\widget\TextWidget.
 */

namespace Drupal\field_quiz\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_quiz_text' widget.
 *
 * @FieldWidget(
 *   id = "field_quiz_question_widget",
 *   module = "field_quiz",
 *   label = @Translation("Your multiple choice question widget."),
 *   field_types = {
 *     "field_quiz_question"
 *   }
 * )
 */
class TextWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $values = $items[$delta]->getValue();

    $widget_answer = $element;
    $widget_correct = $element;
    $widget_answer['#delta'] = $delta;
    $widget_correct['#delta'] = $delta;

    // Textfield answer.
    $widget_answer += array(
      '#type' => 'textfield',
      '#title' => t('Quiz answer'),
      '#description' => t('Enter your possible answer'),
      '#default_value' => $values['answer'],
      '#size' => 50,
      '#maxlength' => 1024,
      '#element_validate' => array(
          array($this, 'validate'),
      ),
      '#suffix' => '<div class="field-field-quiz"></div>',
      '#attributes' => array('class' => array('edit-field-field-quiz')),
    );

    // Checkbox correct answer.
    $widget_correct += array(
      '#type' => 'checkbox',
      '#title' => t('correct answer?'),
      '#description' => t('Set this checkbox active, if this answer is correct'),
      '#default_value' => $values['correct'],
      '#element_validate' => array(
          array($this, 'validate'),
      ),
      '#suffix' => '<div class="field-field_quiz"></div>',
      '#attributes' => array('class' => array('edit-field-field-quiz')),
    );

    return array('answer' => $widget_answer, 'correct' => $widget_correct);
  }

  /**
   * Validate the color text field.
   */
  public function validate($element, FormStateInterface $form_state) {

    // @todo Add Validation.

  }


}
