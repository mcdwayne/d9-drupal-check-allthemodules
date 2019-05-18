<?php

namespace Drupal\og_sm_admin_menu\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\og\OgAccessInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\toolbar\Controller\ToolbarController as ToolbarControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the access handler of the toolbar controller.
 */
class ToolbarController extends ToolbarControllerBase {

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
   * Constructs ToolbarController object.
   *
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The og access service.
   */
  public function __construct(SiteManagerInterface $site_manager, OgAccessInterface $og_access) {
    $this->siteManager = $site_manager;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og_sm.site_manager'),
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkSubTreeAccess($hash) {
    $access = parent::checkSubTreeAccess($hash);

    $site_access_result = AccessResult::neutral();
    $site = $this->siteManager->currentSite();
    if ($site) {
      $site_access_result = $this->ogAccess->userAccess($site, 'access toolbar', $this->currentUser());
    }
    $access->orIf($site_access_result);
    return $access;
  }

}
