<?php

namespace Drupal\flexiform\FormComponent;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for component types that are createable.
 *
 * Component types that implement this interface can be defined in a repeatable
 * custom fashion.
 */
interface FormComponentTypeCreateableInterface extends FormComponentTypeInterface {

  /**
   * Get a form for adding a component of this type.
   *
   * @param array $form
   *   The part of the form array that should contain the component plugin
   *   settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   A form array for setting the options of a component. Normally broken
   *   into settings and third_party_settings.
   */
  public function addComponentForm(array $form, FormStateInterface $form_state);

  /**
   * Validate the add component form.
   *
   * @param array $form
   *   The part of the form array that should contain the component plugin
   *   settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addComponentFormValidate(array $form, FormStateInterface $form_state);

  /**
   * Submit the add component options form.
   *
   * Make sure the desired options values are set to the same parents as the
   * supplied form element.
   *
   * @param array $form
   *   The part of the form array that should contain the component plugin
   *   settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addComponentFormSubmit(array $form, FormStateInterface $form_state);

}
