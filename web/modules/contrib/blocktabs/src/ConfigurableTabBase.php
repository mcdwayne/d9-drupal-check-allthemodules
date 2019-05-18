<?php

namespace Drupal\blocktabs;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for configurable tab.
 *
 * @see \Drupal\blocktabs\Annotation\Tab
 * @see \Drupal\blocktabs\ConfigurableTabInterface
 * @see \Drupal\blocktabs\TabInterface
 * @see \Drupal\blocktabs\TabBase
 * @see \Drupal\blocktabs\TabManager
 * @see plugin_api
 */
abstract class ConfigurableTabBase extends TabBase implements ConfigurableTabInterface {

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

}
