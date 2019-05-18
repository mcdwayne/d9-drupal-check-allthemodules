<?php

namespace Drupal\transcoding\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

abstract class TranscoderBase extends PluginBase implements TranscoderPluginInterface {

  /**
   * @inheritDoc
   */
  public function getConfiguration() {
    return $this->configuration;
  }
  /**
   * @inheritDoc
   */

  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * @inheritDoc
   */
  public function validateJobForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

}
