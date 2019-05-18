<?php

namespace Drupal\flexiform\FormElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for form element plugins.
 */
interface FormElementInterface {

  /**
   * Build the form element.
   */
  public function form(array $form, FormStateInterface $form_state);

  /**
   * Validate the form.
   */
  public function formValidate(array $form, FormStateInterface $form_state);

  /**
   * Submit the form.
   */
  public function formSubmit(array $form, FormStateInterface $form_state);

  /**
   * Build entities.
   */
  public function buildEntities(array $form, FormStateInterface $form_state);

}
