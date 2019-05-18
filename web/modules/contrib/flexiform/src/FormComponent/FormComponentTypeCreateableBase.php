<?php

namespace Drupal\flexiform\FormComponent;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for component types that are createable.
 *
 * Component types that implement this interface can be defined in a repeatable
 * custom fashion.
 */
abstract class FormComponentTypeCreateableBase extends FormComponentTypeBase implements FormComponentTypeCreateableInterface {

  /**
   * {@inheritdoc}
   */
  public function addComponentForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function addComponentFormValidate(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function addComponentFormSubmit(array $form, FormStateInterface $form_state) {}

}
