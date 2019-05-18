<?php

namespace Drupal\alert_box\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'Alert Box' block.
 *
 * @Block(
 *   id = "alert_box_block",
 *   admin_label = @Translation("Alert Box"),
 * )
 */
class AlertBoxBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('alert_box.settings');
    if ($config->get('enabled')) {
      $this->configuration['label'] = $this->t('Alert Message');
      return array(
        '#children' => check_markup($config->get('message.value'), $config->get('message.format')),
      );
    } else {
      return array();
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
  	return Cache::mergeTags(['config:alert_box.settings'], parent::getCacheTags());
  }
}