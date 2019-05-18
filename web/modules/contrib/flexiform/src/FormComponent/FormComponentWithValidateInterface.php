<?php

namespace Drupal\flexiform\FormComponent;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for form components that contain their own validation logic.
 */
interface FormComponentWithValidateInterface extends FormComponentInterface {

  /**
   * Perform validation logic.
   *
   * @var array $form
   *   Form array
   * @var \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function formValidate(array $form, FormStateInterface $form_state);

}
