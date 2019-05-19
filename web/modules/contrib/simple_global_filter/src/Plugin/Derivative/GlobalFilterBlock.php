<?php

namespace Drupal\simple_global_filter\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Description of GlobalFilterBlock
 *
 * @author alberto
 */
class GlobalFilterBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $global_filters = \Drupal::entityTypeManager()->getStorage('global_filter')->loadMultiple();
    foreach($global_filters as $id => $global_filter) {
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['admin_label'] = $global_filter->label();
      $this->derivatives[$id]['id'] = $global_filter->id();
    }
    return $this->derivatives;
  }

}
