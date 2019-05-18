<?php

namespace Drupal\aframe;

use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AFrameComponentPluginBase.
 *
 * @package Drupal\aframe
 */
abstract class AFrameComponentPluginBase extends PluginSettingsBase implements AFrameComponentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = $configuration['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->getSetting($this->pluginId);
  }

}
