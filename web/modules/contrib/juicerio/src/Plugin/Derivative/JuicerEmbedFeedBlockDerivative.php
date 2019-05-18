<?php

namespace Drupal\juicerio\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

class JuicerEmbedFeedBlockDerivative extends DeriverBase  {

  public function getDerivativeDefinitions($base_plugin_definition) {
    $count = \Drupal::config('juicerio.settings')->get('juicer_blocks');

    for ($delta = 1; $delta <= $count; $delta++) {
      $info = t('Juicer Embed Feed');
      $this->derivatives['juicerio_' . $delta] = $base_plugin_definition;
      $this->derivatives['juicerio_' . $delta]['admin_label'] = t('@info @delta', array('@info' => $info, '@delta' => $delta));
    }

    return $this->derivatives;
  }
}