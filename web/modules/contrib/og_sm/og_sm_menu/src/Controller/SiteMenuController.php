<?php

namespace Drupal\og_sm_menu\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og_menu\Controller\OgMenuInstanceController;
use Drupal\og_sm_menu\SiteMenuManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The site menu controller class.
 */
class SiteMenuController extends ControllerBase {

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The og menu instance controller.
   *
   * @var \Drupal\og_menu\Controller\OgMenuInstanceController
   */
  protected $ogMenuInstanceController;

  /**
   * The site manager service.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * The site menu manager service.
   *
   * @var \Drupal\og_sm_menu\SiteMenuManagerInterface
   */
  protected $siteMenuManager;

  /**
   * Constructs an SiteMenuController object.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver service.
   * @param SiteMenuManagerInterface $site_menu_manager
   *   The site menu manager service.
   */
  public function __construct(ClassResolverInterface $class_resolver, SiteMenuManagerInterface $site_menu_manager) {
    $this->ogMenuInstanceController = $class_resolver->getInstanceFromDefinition(OgMenuInstanceController::class);
    $this->siteMenuManager = $site_menu_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('class_resolver'),
      $container->get('og_sm.site_menu_manager')
    );
  }

  /**
   * Provides the menu link creation form.
   *
   * @return array
   *   Returns the menu link creation form.
   */
  public function addLink() {
    return $this->ogMenuInstanceController->addLink($this->siteMenuManager->getCurrentMenu());
  }

  /**
   * Access callback for the "add link" route.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to determine access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function addLinkAccess(AccountInterface $account) {
    $site_menu = $this->siteMenuManager->getCurrentMenu();

    if ($site_menu) {
      return $this->ogMenuInstanceController->addLinkAccess($site_menu, $account);
    }

    return AccessResult::neutral();
  }

}
