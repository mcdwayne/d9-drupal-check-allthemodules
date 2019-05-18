<?php

namespace Drupal\js_component;

use Drupal\Core\Form\FormStateInterface;

/**
 * Define a JS component form interface.
 */
interface JSComponentFormInterface {

  /**
   * Validate component form elements.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   */
  public function validateComponentFormElements(array $form, FormStateInterface $form_state);

  /**
   * Attach component form elements.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param array $configuration
   *   An array of component elements configurations.
   */
  public function attachComponentFormElements(array &$form, FormStateInterface $form_state, array $configuration);
}
