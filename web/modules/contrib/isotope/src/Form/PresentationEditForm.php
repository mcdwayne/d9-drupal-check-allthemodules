<?php

/**
 * @file
 * Contains Drupal\isotope\Form\PresentationEditForm.
 */

namespace Drupal\isotope\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class PresentationEditForm
 *
 * Provides the edit form for our Presentation entity.
 *
 * @package Drupal\isotope\Form
 *
 * @ingroup isotope
 */
class PresentationEditForm extends PresentationFormBase {

  /**
   * Returns the actions provided by this form.
   *
   * For the edit form, we only need to change the text of the submit button.
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
    $actions['submit']['#value'] = $this->t('Update Presentation');
    return $actions;
  }

}
