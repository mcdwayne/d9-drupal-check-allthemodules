<?php

namespace Drupal\smallads_group\Plugin\GroupContentEnabler;

use Drupal\smallads\Entity\SmalladType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class SmalladDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (SmalladType::loadMultiple() as $name => $ad_type) {
      $label = $ad_type->label();
      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group small ad') . " ($name)",
        'description' => t('Adds %type content to groups.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
