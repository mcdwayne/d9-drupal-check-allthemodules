<?php

namespace Drupal\heatmap_me;

class Heatmap {

  /**
   * Adds the heatmap.me script library to the current
   * page if the user has the right permissions.
   * 
   * @param array $attachments 
   * @return void
   */
  public static function hook_page_attachments_alter(array &$attachments) {
    $user = \Drupal::currentUser();
    // User ID=1 is Drupal's hardocded administrator
    // and has permissions for everything, but we don't
    // want anything to be tracked for this user.
    if ((int) $user->id() === 1) {
      return;
    }
    if ($user->hasPermission('heatmap tracking')) {
      $attachments['#attached']['library'][] = "heatmap_me/heatmap";
    }
  }
}
