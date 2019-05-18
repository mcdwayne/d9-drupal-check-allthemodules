<?php

/**
 * @file
 * Contains \Drupal\nepali_calendar\Plugin\Block\NepaliCalendarDateBlock.
 */

namespace Drupal\nepali_calendar\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Nepali date' block.
 *
 * @Block(
 *   id = "nepali_calendar_date_block",
 *   admin_label = @Translation("Nepali date")
 * )
 */
class NepaliCalendarDateBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#type' => 'markup',
      '#markup' => nepali_calendar_block_contents(),
    );
  }

}
