<?php

namespace Drupal\developer_suite\Hook;

/**
 * Interface HookThemeInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookThemeInterface {

  /**
   * Executes the theme hook.
   *
   * @param array $existing
   *   An array of existing implementations.
   * @param string $type
   *   Whether a theme, module, etc. is being processed.
   * @param string $theme
   *   The name of the theme that is being processed.
   * @param string $path
   *   The path of the theme or module.
   *
   * @return array
   *   An associative array of information about theme implementations.
   */
  public function execute(array $existing, $type, $theme, $path);

}
