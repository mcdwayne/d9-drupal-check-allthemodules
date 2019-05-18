<?php

namespace Drupal\cloud\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\Plugin\views\access\Permission;
use Symfony\Component\Routing\Route;

/**
 * Plugin that determines the permission of a view based on the cloud_context.
 *
 * This plugin determines if a particular view can be accessed based on the
 * cloud_context that is in the url and a user configured permission.
 *
 * When a cloud config entity is added, a new set of permissions is added.
 * That permission determines if a user can access a particular cloud
 * that is part of that cloud context.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "ViewsCloudContextAccess",
 *   title = @Translation("Access based on user configured permission and Cloud Context permission"),
 * )
 */
class ViewsCloudContextAccess extends Permission {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('User Permission and Cloud Context access check');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $cloud_context = \Drupal::routeMatch()->getParameter('cloud_context');
    if (!isset($cloud_context)) {
      return FALSE;
    }
    if ($account->hasPermission('view ' . $cloud_context) && $account->hasPermission($this->options['perm'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    // Use a _custom_access requirement to determine
    // if the the user can access this particular view.
    $route->setRequirement('_custom_access', '\Drupal\cloud\Controller\CloudConfigController::access');
    $route->setOption('perm', $this->options['perm']);
  }

}
