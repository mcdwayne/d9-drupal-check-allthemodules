<?php

namespace Drupal\ajax_login_popup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use \Drupal\Core\Link;


/**
 * Provides a 'AjaxLoginPopupLoginBlock' block.
 *
 * @Block(
 *  id = "ajax_login_form_popup",
 *  admin_label = @Translation("Ajax Login form popup"),
 * )
 */
class AjaxLoginPopupBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromRoute('ajax_login_popup.ajax');
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
	$button_name = \Drupal::config('redirection_config_form.settings')->get('ajax_button');
    $link = Link::fromTextAndUrl($button_name, $url)->toString();
    $build = [];
	if (\Drupal::currentUser()->isAnonymous()) {
      $build['login_popup_block']['#markup'] = '<div class="Login-popup-link">' . $link . '</div>';
	}
      $build['login_popup_block']['#attached']['library'][] = 'core/drupal.dialog.ajax';
      return $build;
  }

}
