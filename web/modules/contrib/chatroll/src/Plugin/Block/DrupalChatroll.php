<?php
/**
 * @file
 * Chatroll Live Chat platform extension
 * License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace Drupal\chatroll\Plugin\Block;

/**
 * Drupal Chatroll module extension
 * License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
class DrupalChatroll extends Chatroll {
  // Default width/height.
  public $width = '450';
  public $height = '350';
  public $showlink = 1;

  /**
   * Override: Append width/height and SSO params
   */
  public function appendPlatformDefaultAttr($attr) {
    $attr['platform'] = 'drupal';

    if ($this->showlink) {
      $attr['linkurl'] = '/solutions/drupal-chat-module';
      $attr['linktxt'] = 'Drupal chat';
    }
    else {
      $attr['linkurl'] = '';
      $attr['linktxt'] = '';
    }

    // Values set via module params.
    $attr['height'] = $this->height;
    $attr['width'] = $this->width;

    // Append User info for SSO integration.
    $user = \Drupal::currentUser();
    if ($user->id()) {
      $attr['uid'] = $user->id();
      $attr['uname'] = $user->getDisplayName();
    }

    // Moderation permission managed in site Permissions settings.
    $attr['ismod'] = $user->hasPermission('moderate chatroll');

    // Note: Add your custom profile URL here.
    // $attr['ulink]' = '';.
    return $attr;
  }
}
