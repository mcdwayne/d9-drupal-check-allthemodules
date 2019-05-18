<?php

namespace Drupal\entity_language_fallback\Plugin\search_api\datasource;

use Drupal\search_api\Plugin\search_api\datasource\ContentEntityDeriver;

/**
 * Derives a datasource plugin definition for every content entity type.
 */
class ContentEntityFallbackDeriver extends ContentEntityDeriver {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $plugin_derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
      foreach ($plugin_derivatives as &$derivative) {
        $derivative['label'] .= $this->t(' (with language fallback)');
      }
      $this->derivatives = $plugin_derivatives;
    }

    return $this->derivatives;
  }

}
