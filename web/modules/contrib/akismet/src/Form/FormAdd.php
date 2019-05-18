<?php

namespace Drupal\akismet\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormAddForm.
 *
 * Provides the add form for our Akismet Form entity.
 *
 * @package Drupal\akismet\Form
 *
 * @ingroup akismet
 */
class FormAdd extends FormFormBase {

  /**
   * Returns the actions provided by this form.
   *
   * For our add form, we only need to change the text of the submit button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create Protected Akismet Form');
    return $actions;
  }

}
