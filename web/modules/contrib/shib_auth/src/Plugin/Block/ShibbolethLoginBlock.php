<?php

namespace Drupal\shib_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'ShibbolethLoginBlock' block.
 *
 * @Block(
 *  id = "shibboleth_login_block",
 *  admin_label = @Translation("Shibboleth login block"),
 * )
 */
class ShibbolethLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = \Drupal::config('shib_auth.advancedsettings');
    $current_user = \Drupal::currentUser();

    $markup = '<div class="shibboleth-links">';
    if (!$current_user->id()) {
      $markup .= '<div class="shibboleth-login">' . shib_auth_get_login_link() . '</div>';
    }
    else {
      $markup .= '<div class="shibboleth-logout">' . shib_auth_get_logout_link() . '</div>';
    }
    $markup .= '</div>';

    $build['shibboleth_login_block'] = [
      '#markup' => $markup,
      '#cache' => [
        'contexts' => [
          'user.roles:anonymous',
        ],
      ],
    ];

    if (!$config->get('url_redirect_login')) {
      // Redirect is not set, so it will use the current path. That means it
      // will differ per page.
      $build['shibboleth_login_block']['#cache']['contexts'][] = 'url.path';
      $build['shibboleth_login_block']['#cache']['contexts'][] = 'url.query_args';
    }

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['shibboleth_login_block']);
  }

}
