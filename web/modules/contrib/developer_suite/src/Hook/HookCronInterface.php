<?php

namespace Drupal\developer_suite\Hook;

/**
 * Interface HookCronInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookCronInterface {

  /**
   * Executes the cron hook.
   */
  public function execute();

}
