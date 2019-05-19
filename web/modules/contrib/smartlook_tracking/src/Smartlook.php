<?php

namespace Drupal\smartlook_tracking;

/**
 * Class Smartlook.
 *
 * @package Drupal\smartlook_tracking
 */
class Smartlook {

  /**
   * Insert JavaScript to the appropriate scope/region of the page.
   *
   * @param array $attachments
   *   An array that you can add attachments to.
   */
  public static function hookPageAttachments(array &$attachments) {
    $user = \Drupal::currentUser();
    $config = \Drupal::config('smartlook_tracking.settings');

    // User ID=1 is Drupal's hardcoded administrator and has permissions for
    // everything, but we don't want anything to be tracked for this user.
    if ((int) $user->id() === 1 || !$user->hasPermission('smartlook tracking')) {
      return;
    }

    $key = $config->get('account');

    if (!empty($key) && _smartlook_tracking_visibility_pages()) {
      // Build tracker code.
      $script = "window.smartlook||(function(d) {";
      $script .= "var o=smartlook=function(){ o.api.push(arguments)},h=d.getElementsByTagName('head')[0];";
      $script .= "var c=d.createElement('script');o.api=new Array();c.async=true;c.type='text/javascript';";
      $script .= "c.charset='utf-8';c.src='//rec.smartlook.com/recorder.js';h.appendChild(c);";
      $script .= "})(document);";
      $script .= "smartlook('init', '" . $key . "');";

      $attachments['#attached']['html_head'][] = [
        [
          '#tag'   => 'script',
          '#value' => $script,
        ],
        'smartlook_tracking_script',
      ];
    }
  }

}
