<?php

namespace Drupal\edstep\Form;

use Drupal\Core\Entity\ContentEntityForm;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the edstep course edit forms.
 */
class EdstepCourseForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['course_id'] = [
      '#type' => 'textfield',
      '#title' => 'EdStep Course ID',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message(t('The course has been added.'), 'status');
  }
}
