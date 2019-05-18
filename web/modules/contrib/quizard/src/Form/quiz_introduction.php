<?php

/**
 * @file
 * Contains \Drupal\quizard\Form\quiz_introduction.
 */

namespace Drupal\quizard\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class quiz_introduction extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_introduction';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $step = $form_state->getBuildInfo()['callback_object']->getStep($cached_values);
    $introduction = $cached_values[$step];
    $form['quiz_introduction'] = [
      '#type' => 'item',
      '#markup' => !empty($introduction['field_quiz_introduction'][0]['value']) ? $introduction['field_quiz_introduction'][0]['value'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Introduction step. Nothing to save here.
  }

}
