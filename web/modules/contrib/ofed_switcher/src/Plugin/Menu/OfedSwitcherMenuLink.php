<?php

namespace Drupal\ofed_switcher\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

class OfedSwitcherMenuLink extends MenuLinkDefault implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    // TODO: use proper service injection.
    $is_admin = \Drupal::service('router.admin_context')->isAdminRoute();

    if ($this->pluginDefinition['route_name'] == 'ofed_switcher.switch_to_backend') {
      if ($is_admin) {
        return 0;
      }
    }

    if ($this->pluginDefinition['route_name'] == 'ofed_switcher.switch_to_frontend') {
      if (!$is_admin) {
        return 0;
      }
    }

    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['path_is_admin'];
  }

}