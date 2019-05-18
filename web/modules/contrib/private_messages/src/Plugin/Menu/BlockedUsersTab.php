<?php

namespace Drupal\private_messages\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

class BlockedUsersTab extends LocalTaskDefault {

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Current route context user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Gets the current active user.
   *
   * @todo: https://www.drupal.org/node/2105123 put this method in
   *   \Drupal\Core\Plugin\PluginBase instead.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  protected function currentUser() {
    if (!$this->currentUser) {
      $this->currentUser = \Drupal::currentUser();
    }
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = null)
  {
    $output = $this->pluginDefinition['title'];

    $count = $this->user->get('field_blocked_user')
      ->filterEmptyItems()
      ->count();
    $output .= " ($count)";

    return (string)$output;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $this->user = $route_match->getParameter('user');

    return array(
      'user' => $this->user->Id()
    );
  }

}
