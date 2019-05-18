<?php

namespace Drupal\invite\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for invite routes.
 */
class InviteAccessController extends ControllerBase {

  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(CurrentRouteMatch $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
       $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function withdrawInviteAccess(AccountInterface $account) {
    $badge_admin = $account->hasPermission('administer invite settings');
    if ($badge_admin) {
      return AccessResult::allowed();
    }
    else {
      /** @var \Drupal\invite\InviteInterface $invite */
      $invite_from_url = $this->routeMatch->getParameter('invite');
      return AccessResult::allowedIf($account->id() && $account->id() == $invite_from_url->getOwnerId() && $account->hasPermission('resend own invitations'))
        ->cachePerPermissions()
        ->cachePerUser();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resendInviteAccess(AccountInterface $account) {
    $invite_admin = $account->hasPermission('administer invite settings');
    if ($invite_admin) {
      return AccessResult::allowed();
    }
    else {
      /** @var \Drupal\invite\InviteInterface $invite */
      $invite_from_url = $this->routeMatch->getParameter('invite');
      return AccessResult::allowedIf($account->id() && $account->id() == $invite_from_url->getOwnerId())
        ->cachePerPermissions()
        ->cachePerUser();
    }
  }

}
