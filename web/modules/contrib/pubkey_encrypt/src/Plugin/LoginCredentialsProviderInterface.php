<?php

namespace Drupal\pubkey_encrypt\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for ice cream flavor plugins.
 */
interface LoginCredentialsProviderInterface extends PluginInspectionInterface {

  /**
   * Return name of the asymmetric keys generator plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return description of the asymmetric keys generator plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Fetch relevant credentials from the user login form.
   *
   * @param mixed $form
   *   Nested array of form elements that comprise the user login form.
   * @param FormStateInterface $form_state
   *   Current state of user login form.
   *
   * @return string
   *   The relevant login credential for a user which Pubkey Encrypt should use.
   */
  public function fetchLoginCredentials($form, FormStateInterface &$form_state);

  /**
   * Fetch old and new credentials from the user form.
   *
   * @param mixed $form
   *   Nested array of form elements that comprise the user form.
   * @param FormStateInterface $form_state
   *   Current state of user login form.
   *
   * @return string[]
   *   Array of strings indexed with 'old' and 'new'
   */
  public function fetchChangedLoginCredentials($form, FormStateInterface &$form_state);

}
