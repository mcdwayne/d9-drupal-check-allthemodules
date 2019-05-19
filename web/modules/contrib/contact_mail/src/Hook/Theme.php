<?php

namespace Drupal\contact_mail\Hook;

/**
 * @file
 * Contains \Drupal\contact_mail\Hook\Theme.
 */

/**
 * Theme.
 */
class Theme {

  /**
   * Hook.
   */
  public static function hook() {
    return [
      'contact_mail' => [
        'template' => 'submission',
        'variables' => [
          'type' => FALSE,
          'submission' => [],
        ],
      ],
    ];
  }

}
