<?php

namespace Drupal\panels_extended\Plugin\PanelsPattern;

use Drupal\panels\Plugin\PanelsPattern\PanelsPatternInterface;

/**
 * Provides an interface to add extra urls for the blocks on panel pages.
 */
interface ExtendedPanelsPatternInterface extends PanelsPatternInterface {

  /**
   * Gets the url for enabling a block.
   *
   * @param string $tempstore_id
   *   The tempstore identifier.
   * @param string $machine_name
   *   The tempstore key.
   * @param string $block_id
   *   The unique id of the block in this panel.
   * @param string $destination
   *   The destination to which to redirect after submission.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  public function getBlockEnableUrl($tempstore_id, $machine_name, $block_id, $destination = NULL);

  /**
   * Gets the url for disabling a block.
   *
   * @param string $tempstore_id
   *   The tempstore identifier.
   * @param string $machine_name
   *   The tempstore key.
   * @param string $block_id
   *   The unique id of the block in this panel.
   * @param string $destination
   *   The destination to which to redirect after submission.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  public function getBlockDisableUrl($tempstore_id, $machine_name, $block_id, $destination = NULL);

  /**
   * Gets the url for scheduling a block.
   *
   * @param string $tempstore_id
   *   The tempstore identifier.
   * @param string $machine_name
   *   The tempstore key.
   * @param string $block_id
   *   The unique id of the block in this panel.
   * @param string $destination
   *   The destination to which to redirect after submission.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  public function getBlockScheduleUrl($tempstore_id, $machine_name, $block_id, $destination = NULL);

}
