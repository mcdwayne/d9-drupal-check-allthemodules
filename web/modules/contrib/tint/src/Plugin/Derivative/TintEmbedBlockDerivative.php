<?php

namespace Drupal\tint\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides a 'TintEmbedBlock' block derivative.
 */
class TintEmbedBlockDerivative extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $count = \Drupal::config('tint.settings')->get('tint_blocks');

    for ($delta = 1; $delta <= $count; $delta++) {
      $info = t('TINT Embed HTML');
      $this->derivatives['tint_' . $delta] = $base_plugin_definition;
      $this->derivatives['tint_' . $delta]['admin_label'] = t('@info', ['@info' => $info]);
    }

    return $this->derivatives;
  }

}
