<?php

namespace Drupal\drd\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Derivative\DeriverInterface;

/**
 * Provides block plugin definitions for drd remote blocks.
 *
 * @see \Drupal\drd\Plugin\Block\Remote
 */
class RemoteBlock extends DeriverBase implements DeriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $site_config = \Drupal::config('drd.general');
    foreach ($site_config->get('remote_blocks') as $module => $blocks) {
      foreach ($blocks as $delta => $label) {
        $id = implode(':', [$module, $delta]);
        $this->derivatives[$id] = $base_plugin_definition;
        $this->derivatives[$id]['admin_label'] = t('Remote block: @label', ['@label' => $label]);
      }
    }
    return $this->derivatives;
  }

}
