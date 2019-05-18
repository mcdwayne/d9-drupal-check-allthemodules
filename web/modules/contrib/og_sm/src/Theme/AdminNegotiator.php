<?php

namespace Drupal\og_sm\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\user\Theme\AdminNegotiator as AdminNegotiatorBase;

/**
 * Sets the active theme on admin pages.
 */
class AdminNegotiator extends AdminNegotiatorBase {

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * The of access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Creates a new AdminNegotiator instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context to determine whether the route is an admin one.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The og access service.
   */
  public function __construct(AccountInterface $user, ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, AdminContext $admin_context, SiteManagerInterface $site_manager, OgAccessInterface $og_access) {
    parent::__construct($user, $config_factory, $entity_manager, $admin_context);
    $this->siteManager = $site_manager;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $applies = parent::applies($route_match);
    if ($applies) {
      return TRUE;
    }

    if (!$this->adminContext->isAdminRoute($route_match->getRouteObject())) {
      return FALSE;
    }

    $site = $this->siteManager->currentSite();
    if (!$site) {
      return FALSE;
    }

    return $this->ogAccess->userAccess($site, 'view the administration theme', $this->user)->isAllowed();
  }

}
