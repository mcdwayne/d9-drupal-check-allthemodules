<?php

namespace Drupal\packages\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Package plugins.
 */
interface PackageInterface extends PluginInspectionInterface {

  /**
   * Return the settings for the package.
   *
   * These should be passed in as configuration when the package is initiated.
   *
   * @return array
   *   An array of settings.
   */
  public function getSettings();

  /**
   * Provide a settings form if your package is configurable.
   *
   * To mark your package as configurable set "configurable" to TRUE in your
   * package annotation.
   *
   * @param array $form
   *   The form to alter.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form's FormStateInterface.
   */
  public function settingsForm(array &$form, FormStateInterface $form_state);

  /**
   * Validate the settings form from settingsForm().
   *
   * @param array $form
   *   The form to alter.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form's FormStateInterface.
   */
  public function validateSettingsForm(array &$form, FormStateInterface $form_state);

  /**
   * Submit the settings form from settingsForm().
   *
   * @param array $form
   *   The form to alter.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form's FormStateInterface.
   *
   * @return array
   *   An array of settings to be saved for the user.
   */
  public function submitSettingsForm(array &$form, FormStateInterface $form_state);

}
