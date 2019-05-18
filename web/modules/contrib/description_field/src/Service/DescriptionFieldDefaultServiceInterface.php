<?php

namespace Drupal\description_field\Service;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface DescriptionFieldDefaultServiceInterface.
 */
interface DescriptionFieldDefaultServiceInterface {

  /**
   * Alter the field storage config form.
   *
   * @param array $form
   *   Nested form element array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   * @param string $form_id
   *   A string representing the name of the form.
   */
  public function alterFieldStorageConfigEditForm(array &$form, FormStateInterface $form_state, $form_id);

  /**
   * Alter the field config edit form.
   *
   * @param array $form
   *   Nested form element array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   * @param string $form_id
   *   A string representing the name of the form.
   */
  public function alterFieldConfigEditForm(array &$form, FormStateInterface $form_state, $form_id);

}
