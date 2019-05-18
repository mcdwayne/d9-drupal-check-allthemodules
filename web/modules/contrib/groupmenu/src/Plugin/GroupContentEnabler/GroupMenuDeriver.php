<?php

namespace Drupal\groupmenu\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides a menu deriver.
 */
class GroupMenuDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives['menu'] = [
      'label' => t('Group menu'),
      'description' => t("Adds menus to groups"),
    ] + $base_plugin_definition;

    return $this->derivatives;
  }

}
