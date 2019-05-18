<?php

namespace Drupal\aframe;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\PluginSettingsInterface;

/**
 * Interface AFrameComponentPluginInterface.
 *
 * @package Drupal\aframe
 */
interface AFrameComponentPluginInterface extends PluginSettingsInterface {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The form structure.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   *
   */
  public function settingsSummary();

  /**
   *
   */
  public function getValue();

}
