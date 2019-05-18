<?php

namespace Drupal\hp\Plugin\hp;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the interface for Human Presence plugins.
 *
 * The HP plugin determines how a form should behave.
 */
interface FormStrategyInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function getLabel();

  /**
   * Alters a form.
   */
  public function formAlter(array &$form, FormStateInterface $form_state);

  /**
   * Form validation callback to add the HP behavior.
   */
  public function hpFormValidation(array &$form, FormStateInterface $form_state);

  /**
   * Whether the plugin is available.
   *
   * @return bool
   *   TRUE if the plugin is available, FALSE otherwise.
   */
  public function access();

}
