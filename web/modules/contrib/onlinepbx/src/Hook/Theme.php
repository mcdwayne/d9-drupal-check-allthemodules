<?php

namespace Drupal\onlinepbx\Hook;

/**
 * Hook Theme.
 */
class Theme {

  /**
   * Hook.
   */
  public static function hook() {
    return [
      'call-online' => [
        'template' => 'online',
        'variables' => ['data' => []],
      ],
    ];
  }

}
