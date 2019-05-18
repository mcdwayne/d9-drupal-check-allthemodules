<?php

namespace Drupal\panels_extended\Plugin\PanelsPattern;

use Drupal\Core\Url;
use Drupal\panels\Plugin\PanelsPattern\DefaultPattern;

/**
 * Pattern which changes the url the buttons for blocks.
 *
 * @PanelsPattern("extended_pattern")
 */
class ExtendedPanelsPattern extends DefaultPattern implements ExtendedPanelsPatternInterface {

  /**
   * {@inheritdoc}
   */
  public function getBlockListUrl($tempstore_id, $machine_name, $region = NULL, $destination = NULL) {
    return Url::fromRoute('panels_extended.select_block', [
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'region' => $region,
      'destination' => $destination,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockEnableUrl($tempstore_id, $machine_name, $block_id, $destination = NULL) {
    return Url::fromRoute('panels_extended.enable_block', [
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'block_id' => $block_id,
      'destination' => $destination,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockDisableUrl($tempstore_id, $machine_name, $block_id, $destination = NULL) {
    return Url::fromRoute('panels_extended.disable_block', [
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'block_id' => $block_id,
      'destination' => $destination,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockScheduleUrl($tempstore_id, $machine_name, $block_id, $destination = NULL) {
    return Url::fromRoute('panels_extended.schedule_block', [
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'block_id' => $block_id,
      'destination' => $destination,
    ]);
  }

}
