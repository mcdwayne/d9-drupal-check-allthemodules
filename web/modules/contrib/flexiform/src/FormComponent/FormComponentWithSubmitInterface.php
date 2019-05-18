<?php

namespace Drupal\flexiform\FormComponent;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for form components that contain their own submission logic.
 */
interface FormComponentWithSubmitInterface extends FormComponentInterface {

  /**
   * Perform submission logic.
   *
   * @var array $form
   *   Form array
   * @var \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function formSubmit(array $form, FormStateInterface $form_state);

}
