<?php

namespace Drupal\plus\Core\Form;

use Drupal\plus\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for an object oriented form alter.
 *
 * @ingroup plugins_form
 */
interface FormAlterInterface {

  /**
   * The alter method to store the code.
   *
   * @param \Drupal\plus\Utility\Element $form
   *   The Element object that comprises the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   String representing the name of the form itself. Typically this is the
   *   name of the function that generated the form.
   */
  public function formAlter(Element $form, FormStateInterface $form_state, $form_id = NULL);

}
