<?php

namespace Drupal\drd\Plugin\Auth;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for DRD Auth plugins.
 */
interface BaseInterface extends PluginInspectionInterface {

  /**
   * Determine if the authentication settings should be saved remotely.
   *
   * @return bool
   *   TRUE if settings should be stored remotely.
   */
  public function storeSettingRemotely();

  /**
   * Build the settings form for authentication plugins.
   *
   * @param array $form
   *   The form array.
   * @param array $condition
   *   The form state object.
   */
  public function settingsForm(array &$form, array $condition);

  /**
   * Retrieve settings values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The authentication settings.
   */
  public function settingsFormValues(FormStateInterface $form_state);

}
