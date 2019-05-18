<?php

namespace Drupal\projects_stats;

/**
 * Interface ProjectsStatsSlackServiceInterface.
 *
 * @package Drupal\projects_stats
 */
interface ProjectsStatsSlackServiceInterface {

  /**
   * Sends message.
   *
   * @return mixed
   */
  public function sendMessage();

}
