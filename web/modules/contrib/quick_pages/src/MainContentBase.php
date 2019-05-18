<?php

/**
 * @file
 * Contains \Drupal\quick_pages\MainContentBase.
 */

namespace Drupal\quick_pages;

use Drupal\Core\Form\FormStateInterface;
use \Drupal\Component\Plugin\PluginBase;

/**
 * Base class for main content plugins.
 */
abstract class MainContentBase extends PluginBase implements MainContentInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

}
