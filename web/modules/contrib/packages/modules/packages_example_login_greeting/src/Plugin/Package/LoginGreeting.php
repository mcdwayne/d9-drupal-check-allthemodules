<?php

namespace Drupal\packages_example_login_greeting\Plugin\Package;

use Drupal\packages\Plugin\PackageBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a package that allows the user to receive a custom greeting upon
 * logging in.
 *
 * See packages_example_login_greeting.module to understand how the package
 * and package state is used.
 *
 * This package does not require any additional permissions but one could be
 * supplied below. See packages_example_page for an example.
 *
 * @Package(
 *   id = "login_greeting",
 *   label = @Translation("Package example: Login greeting"),
 *   description = @Translation("Receive a greeting message when you log in."),
 *   enabled = TRUE,
 *   configurable = TRUE,
 *   default_settings = {
 *     "show_datetime" = TRUE,
 *   }
 * )
 */
class LoginGreeting extends PackageBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface $form_state) {
    // Get the package settings.
    // This will be the default settings unless the user saved their own.
    $settings = $this->getSettings();

    // Give an option to include the datetime in the greeting message.
    $form['show_datetime'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the date and time in your greeting'),
      '#default_value' => $settings['show_datetime'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(array &$form, FormStateInterface $form_state) {
    // No validation needed.
  }

  /**
   * {@inheritdoc}
   */
  public function submitSettingsForm(array &$form, FormStateInterface $form_state) {
    // Return the settings that will be saved in the PackageState.
    return [
      'show_datetime' => $form_state->getValue('show_datetime'),
    ];
  }

  /**
   * Greet the user.
   *
   * @see packages_example_login_greeting_user_login()
   */
  public function greet() {
    // Load the settings.
    $settings = $this->getSettings();

    // Greet the user.
    drupal_set_message($this->t('Welcome back!'));

    // Check if the date and time should be included.
    if ($settings['show_datetime']) {
      drupal_set_message($this->t('The current date and time is: %date', ['%date' => format_date(REQUEST_TIME)]));
    }
  }

}
