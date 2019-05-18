<?php

namespace Drupal\admin_status;

use Drupal\Core\Form\FormStateInterface;

/**
 * An interface for all AdminStatus type plugins.
 */
interface AdminStatusInterface {

  /**
   * Provides a description and any configuration form data.
   *
   * @return mixed
   *   A string or render array description and form options for the AdminStatus
   *   plugin.
   */
  public function description();

  /**
   * Builds a sub-form if needed to configure this plugin.
   *
   * @param array $form
   *   A form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state array.
   * @param array $configValues
   *   An array of saved configuration values for this plugin.
   *
   * @return array
   *   A form array with any configuration form elements needed to configure
   *   this plugin.
   */
  public function configForm(array $form,
                             FormStateInterface $form_state,
                             array $configValues);

  /**
   * Validates the plugin's configuration form values.
   *
   * @param array $form
   *   A form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state array.
   * @param array $configValues
   *   An array of saved configuration values for this plugin.
   */
  public function configValidateForm(array $form,
                                     FormStateInterface $form_state,
                                     array $configValues);

  /**
   * Process submitted data and return configuration values to be saved.
   *
   * @param array $form
   *   A form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state array.
   * @param array $configValues
   *   An array of saved configuration values for this plugin.
   *
   * @return array
   *   An array of configuration values to be saved for this plugin.
   */
  public function configSubmitForm(array $form,
                                   FormStateInterface $form_state,
                                   array $configValues);

  /**
   * Provides the status message to display to the user.
   *
   * @param array $configValues
   *   A form array with any configuration form elements needed to configure
   *   this plugin.
   *
   * @return array
   *   An array, or an array with multiple subarrays, with the following keys:
   *   status - a legal drupal_set_message status value (status, warning, error)
   *   message - a string or a render array for the message
   */
  public function message(array $configValues);

}
