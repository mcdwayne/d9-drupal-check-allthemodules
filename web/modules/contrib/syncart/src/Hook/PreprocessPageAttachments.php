<?php

namespace Drupal\syncart\Hook;

/**
 * PreprocessPageAttachments.
 */
class PreprocessPageAttachments {

  /**
   * Implements hook_page_attachments().
   */
  public static function hook(&$page) {
    $user = \Drupal::routeMatch()->getParameter('user');

    if (!empty($user)) {
      $page['#attached']['library'][] = 'syncart/user';
    }

  }

}
