<?php

namespace Drupal\nginx\Hook;

/**
 * Hook Theme.
 */
class Theme {

  /**
   * Hook.
   */
  public static function hook() {
    return [
      'ngxin-conf' => [
        'variables' => [
          'data' => [],
        ],
      ],
      'ngxin-site' => [
        'variables' => [
          'site' => [],
          'https' => FALSE,
        ],
      ],
      'ngxin-suspend' => [
        'variables' => [
          'site' => [],
        ],
      ],
    ];
  }

}
