<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for defining configurable action plugins.
 *
 * @ingroup drd
 */
interface BaseConfigurableInterface {

  /**
   * Add settings part for the action when executed through the UI.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Returns the form array with added components for the action settings.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * Validates the action settings before the form can be submitted.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state);

  /**
   * Read values from the action settings and add them to the action arguments.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state);

}
