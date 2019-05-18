<?php

/**
 * @file
 * Contains \Drupal\browser_refresh\BrowserRefreshServiceInterface.
 */

namespace Drupal\browser_refresh;

/**
 * Interface BrowserRefreshServiceInterface.
 *
 * @package Drupal\browser_refresh
 */
interface BrowserRefreshServiceInterface {

  /**
   * @param $output
   * @return \Drupal\browser_refresh\BrowserRefreshServiceInterface
   */
  public function setOutput($output);

  /**
   * @param bool $display
   * @return bool
   */
  public function isActive($display = FALSE);

  /**
   * @return int|bool
   */
  public function getPid();

  /**
   * @return void
   */
  public function start();

  /**
   * @return void
   */
  public function stop();

  /**
   * @return void
   */
  public function restart();

}
