<?php

namespace Drupal\crm_core\Theme;

use Drupal\user\Theme\AdminNegotiator;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides the theme negotiator to use the admin theme on crm-core pages.
 */
class CrmCoreAdminNegotiator extends AdminNegotiator {

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->configFactory->get('crm_core.settings')->get('custom_theme');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($this->configFactory->get('crm_core.settings')->get('custom_theme')) {
      if ($route_match->getRouteObject()) {
        $path = $route_match->getRouteObject()->getPath();
        if (strpos($path, '/crm-core') === 0) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
