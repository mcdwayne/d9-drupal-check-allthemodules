<?php

/**
 * @file
 * Contains \Drupal\wayf_dk_login\Plugin\Block\WayfLoginBlock.
 */

namespace Drupal\wayf_dk_login\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'WAYF login' block.
 *
 * @Block(
 *   id = "wayf_login_block",
 *   admin_label = @Translation("WAYF login"),
 *   category = @Translation("User")
 * )
 */
class WayfLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return \Drupal::currentUser()->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('wayf_dk_login.settings');
    $icon_path = drupal_get_path('module', 'wayf_dk_login') . '/icons/' . $config->get('icon');
    $icon_size = wayf_dk_login__icon_size($config->get('icon'));

    return array('#markup' => t('<a href="@url">!icon</a>',
        array('@url' => \Drupal::url('wayf_dk_login.consume'),
        '!icon' => '<img src="/' . $icon_path . '" width=' .
        $icon_size->width . ' height=' . $icon_size->height . ' class="wayf-logo">'))
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return TRUE;
  }

}
