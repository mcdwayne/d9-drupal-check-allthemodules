<?php

namespace Drupal\login_popup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use \Drupal\Core\Link;


/**
 * Provides a 'LoginFormPopup' block.
 *
 * @Block(
 *  id = "login_form_popup",
 *  admin_label = @Translation("Login form popup"),
 * )
 */
class LoginFormPopup extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromRoute('user.login');
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'use-ajax',
          'login-popup-form',
        ),
        'data-dialog-type' => 'modal',
      ),
    );
    $url->setOptions($link_options);
    $link = Link::fromTextAndUrl(t('Log in'), $url)->toString();
    $build = [];
    if (\Drupal::currentUser()->isAnonymous()) {
      $build['login_popup_block']['#markup'] = '<div class="Login-popup-link">' . $link . '</div>';
    }
      $build['login_popup_block']['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

}
