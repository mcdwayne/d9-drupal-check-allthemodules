<?php

namespace Drupal\developer_suite\Hook;

/**
 * Interface HookPreProcessInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookPreProcessInterface {

  /**
   * Executes the pre process hook.
   *
   * @param array $variables
   *   The variables array (modify in place).
   * @param string $hook
   *   The name of the theme hook.
   */
  public function execute(array &$variables, $hook);

}
