<?php

/**
 * @file
 * Contains \Drupal\hooks_tests_hooks\Hooks\TestHook
 */

namespace Drupal\hooks_test_hooks\Hooks;

use Drupal\hooks\HookInterface;

class TestHook implements HookInterface {

  /**
   * {@inheritdoc}
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    $data .= ' Manipulated by ' . static::class;
    ltrim($data);
  }

}
