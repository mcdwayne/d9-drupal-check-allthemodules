<?php

namespace Drupal\views_field_permissions;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface defining a views field permissions service.
 */
interface ViewsUiFormServiceInterface {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function form(array &$form, FormStateInterface &$form_state);

}
