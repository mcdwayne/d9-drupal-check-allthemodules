<?php

namespace Drupal\flexiform\FormComponent;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Interface for form component plugins.
 */
interface FormComponentInterface {

  /**
   * Render the element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The form renderer for setting cacheability metadata.
   */
  public function render(array &$form, FormStateInterface $form_state, RendererInterface $renderer);

  /**
   * Extract the form values.
   *
   * @param array $form
   *   The section of the form corresponding to this component.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function extractFormValues(array $form, FormStateInterface $form_state);

  /**
   * Get the settings form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Get the settings summary.
   *
   * @return string
   *   The setting summary.
   */
  public function settingsSummary();

  /**
   * Get the admin label for the component.
   *
   * @return string
   *   The administrative label for the component.
   */
  public function getAdminLabel();

}
