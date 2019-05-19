<?php

namespace Drupal\tb_megamenu\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides blocks which belong to TB Mega Menu.
 */
class TBMegaMenuBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $menus = menu_ui_get_menus();
    foreach (\Drupal::configFactory()->listAll('tb_megamenu.menu_config.') as $index_id) {
      $info = \Drupal::config($index_id);
      $menu = $info->get('menu');
      if (isset($menus[$menu])) {
        $this->derivatives[$menu] = $base_plugin_definition;
        $this->derivatives[$menu]['admin_label'] = $menus[$menu];

      }
    }
    return $this->derivatives;
  }

}
