<?php

namespace Drupal\micro_theme\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\Core\Routing\AdminContext;


class MicroThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The router admin context service
   *
   * @var |Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Constructor.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   * @param |Drupal\Core\Routing\AdminContext $admin_context
   *   The router admin context.
   */
  public function __construct(SiteNegotiatorInterface $site_negotiator, AdminContext $admin_context) {
    $this->negotiator = $site_negotiator;
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $applies = FALSE;
    $route = $route_match->getRouteObject();
    $is_admin_route = $this->adminContext->isAdminRoute($route);
    if ($is_admin_route) {
      return $applies;
    }

    $site = $this->negotiator->getActiveSite();
    if (!$site instanceof SiteInterface) {
      return $applies;
    }

    if (!$site->get('theme')->isEmpty()) {
      $applies = TRUE;
    }

    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $site = $this->negotiator->getActiveSite();
    return $site->theme->value;
  }

}
