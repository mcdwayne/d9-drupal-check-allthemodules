<?php

namespace Drupal\dream_fields;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for the plugins.
 */
abstract class DreamFieldPluginBase extends PluginBase implements DreamFieldPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm($values, FormStateInterface $form_state) {
    // Empty for plugins with no validation.
  }

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    // Empty for plugins with no form.
  }

}
