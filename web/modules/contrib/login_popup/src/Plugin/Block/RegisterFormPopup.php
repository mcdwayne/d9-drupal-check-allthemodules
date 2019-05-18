<?php

namespace Drupal\login_popup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use \Drupal\Core\Link;


/**
 * Provides a 'RegisterFormPopup' block.
 *
 * @Block(
 *  id = "register_form_popup",
 *  admin_label = @Translation("Register form popup"),
 * )
 */
class RegisterFormPopup extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromRoute('user.register');
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'use-ajax',
          'register-popup-form',
        ),
        'data-dialog-type' => 'modal',
      ),
    );
    $url->setOptions($link_options);
    $link = Link::fromTextAndUrl(t('Register'), $url)->toString();
    $build = [];
    if (\Drupal::currentUser()->isAnonymous()) {
      $build['register_popup_block']['#markup'] = '<div class="Register-popup-link">' . $link . '</div>';
    }
      $build['register_popup_block']['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

}
