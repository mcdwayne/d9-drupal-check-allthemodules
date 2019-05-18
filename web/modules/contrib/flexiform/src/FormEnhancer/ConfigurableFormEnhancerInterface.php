<?php

namespace Drupal\flexiform\FormEnhancer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for configurable form enhancers.
 */
interface ConfigurableFormEnhancerInterface extends FormEnhancerInterface {

  /**
   * The configuration form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form with any additions.
   */
  public function configurationForm(array $form, FormStateInterface $form_state);

  /**
   * The configuration form validation callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function configurationFormValidate(array $form, FormStateInterface $form_state);

  /**
   * The configuration form submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state);

  /**
   * Get the configuration.
   *
   * @return array
   *   The configuration for the enhancer.
   */
  public function getConfiguration();

  /**
   * Set the configuration for the enhancer.
   *
   * @param array $configuration
   *   The configuration array.
   *
   * @return self
   *   The form enhancer.
   */
  public function setConfiguration(array $configuration);

}
