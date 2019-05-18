<?php

namespace Drupal\private_messages\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DialogTab.
 *
 * @package Drupal\private_messages\Plugin\Menu
 */
class DialogTab extends LocalTaskDefault {

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
  public function getTitle(Request $request = NULL) {
    $output = $this->pluginDefinition['title'];
    $param = NULL;

    if (isset($this->pluginDefinition['options']['param'])) {
      $param = $this->pluginDefinition['options']['param'];
    }

    $count = $this->getDialogsCount($param);
    $output .= " ($count)";

    return (string) $output;
  }

  /**
   * Gets dialogs count for specified user attribute or return all.
   *
   * @param string|null $attribute
   *   Optional attribute.
   *
   * @return int
   *   Returns count.
   */
  private function getDialogsCount($attribute = NULL) {
    $count = \Drupal::entityQuery('dialog');

    if ($attribute) {
      $count->condition($attribute, $this->user->id());
    }
    else {
      $condition = $count->orConditionGroup()
        ->condition('uid', $this->user->id())
        ->condition('recipient', $this->user->id());
      $count->condition($condition);
    }

    return $count->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $this->user = $route_match->getParameter('user');
    return [
      'user' => $this->user->Id(),
    ];
  }

}
