<?php

namespace Drupal\login_popup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use \Drupal\Core\Link;


/**
 * Provides a 'LoginAndRegisterPopup' block.
 *
 * @Block(
 *  id = "login_register_form_popup",
 *  admin_label = @Translation("Login And Register Form popup"),
 * )
 */
class LoginAndRegisterPopup extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $url_register = Url::fromRoute('user.register');
    $url_login = Url::fromRoute('user.login');
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'use-ajax',
          'login-popup-form',
        ),
        'data-dialog-type' => 'modal',
      ),
    );
    $url_register->setOptions($link_options);
    $url_login->setOptions($link_options);
    $link_register = Link::fromTextAndUrl(t('Register'), $url_register)->toString();
    $link_login = Link::fromTextAndUrl(t('Log in'), $url_login)->toString();
    $build = [];
    if (\Drupal::currentUser()->isAnonymous()) {
      $build['login_register_popup_block']['#markup'] = '<div class="Login-Register-popup-link"><span>' . $link_login . '</span> | <span>' . $link_register . '</span></div>';
    }
      $build['login_register_popup_block']['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

}
