<?php

namespace Drupal\plus\Core\Form;

use Drupal\plus\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for validating an object oriented form alter.
 *
 * @ingroup plugins_form
 */
interface FormValidateInterface {

  /**
   * Form validation handler.
   *
   * @param \Drupal\plus\Utility\Element $form
   *   The Element object that comprises the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function formValidate(Element $form, FormStateInterface $form_state);

}
