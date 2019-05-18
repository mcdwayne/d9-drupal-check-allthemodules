<?php

/**
 * @file
 * Contains \Drupal\quizard\Form\quiz_multiple_choice.
 */

namespace Drupal\quizard\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class quiz_multiple_choice extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_multiple_choice';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $step = $form_state->getBuildInfo()['callback_object']->getStep($cached_values);
    $question = $cached_values[$step];
    $choices = array();
    foreach ($question['field_quiz_multi_choice_choices'] as $choice) {
      $choices += array(
        $choice['value'] => $choice['value'],
      );
    }

    $form['field_quiz_multi_choice_quest'] = [
      '#type' => 'item',
      '#markup' => !empty($question['field_quiz_multi_choice_quest'][0]['value']) ? $question['field_quiz_multi_choice_quest'][0]['value'] : '',
    ];
    $form[$step] = [
      '#type' => 'radios',
      '#options' => $choices,
      '#default_value' => !empty($cached_values['answers'][$step]) ? $cached_values['answers'][$step] : '',

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
