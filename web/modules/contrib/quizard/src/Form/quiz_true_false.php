<?php

/**
 * @file
 * Contains \Drupal\quizard\Form\quiz_true_false.
 */

namespace Drupal\quizard\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class quiz_true_false extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_true_false';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $step = $form_state->getBuildInfo()['callback_object']->getStep($cached_values);
    $question = $cached_values[$step];
    $form['question'] = [
      '#type' => 'item',
      '#markup' => !empty($question['field_quiz_true_false_quest'][0]['value']) ? $question['field_quiz_true_false_quest'][0]['value'] : '',
    ];

    $form[$step] = [
      '#type' => 'radios',
      '#options' => array(1 => 'True', 0 => 'False'),
      '#default_value' => !empty($cached_values['answers'][$step]) ? $cached_values['answers'][$step] : array(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $step = $form_state->getBuildInfo()['callback_object']->getStep($cached_values);
    $cached_values['answers'][$step] = $form_state->getValue($step);
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
