<?php

/**
 * @file
 * Contains \Drupal\login_history\Plugin\Block\LastLoginBlock.
 */

namespace Drupal\login_history\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a block with information about the user's last login.
 *
 * @Block(
 *   id = "last_login_block",
 *   admin_label = @Translation("Last login"),
 *   category = @Translation("User"),
 * )
 */
class LastLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();
    if ($last_login = login_history_last_login()) {
      $request = \Drupal::request();
      $hostname = $last_login->hostname == $request->getClientIP() ? t('this IP address') : $last_login->hostname;
      $user_agent = $last_login->user_agent == $request->server->get('HTTP_USER_AGENT') ? t('this browser') : $last_login->user_agent;
      $build['last_login']['#markup'] = '<p>' . t('You last logged in from @hostname using @user_agent.', array('@hostname' => $hostname, '@user_agent' => $user_agent)) . '</p>';
      $user = \Drupal::currentUser();
      if ($user->hasPermission('view own login history')) {
        $build['view_report'] = [
          '#type' => 'more_link',
          '#title' => $this->t('View your login history'),
          '#url' => Url::fromRoute('login_history.user_report', ['user' => $user->id()]),
        ];
      }
    }
    // Cache by session.
    $build['#cache'] = [
      'contexts' => [
        'session',
      ],
    ];
    return $build;
  }

}
