<?php

namespace Drupal\authorization_code;

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements PluginFormInterface.
 *
 * @see \Drupal\Core\Plugin\PluginFormInterface
 */
trait PluginFormTrait {

  /**
   * Builds the configuration form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Validates the configuration form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation is not required.
  }

  /**
   * Submits the configuration form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Submission is not required.
  }

}
