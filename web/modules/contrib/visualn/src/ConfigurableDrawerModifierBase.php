<?php

namespace Drupal\visualn;

use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Plugin\VisualNDrawerModifierBase;

/**
 * Provides a base class for configurable image effects.
 *
 * @see \Drupal\visualn\Annotation\VisualNDrawerModifier
 * @see \Drupal\visualn\ConfigurableDrawerModifierInterface
 * @see \Drupal\visualn\Plugin\VisualNDrawerModifierInterface
 * @see \Drupal\visualn\Plugin\VisualNDrawerModifierBase
 * @see \Drupal\visualn\Plugin\VisualNDrawerModifierManager
 * @see plugin_api
 */
abstract class ConfigurableDrawerModifierBase extends VisualNDrawerModifierBase implements ConfigurableDrawerModifierInterface {

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

