<?php

namespace Drupal\packages\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Package plugins.
 */
abstract class PackageBase extends PluginBase implements PackageInterface {

  use StringTranslationTrait;

  /**
   * The package settings.
   *
   * This contains the default package settings as well as any overridden
   * by the user. This will only be populated if the package is initiated
   * via the packages service rather than the plugin manager.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Store the settings.
    $this->settings = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitSettingsForm(array &$form, FormStateInterface $form_state) {
    return [];
  }

}
