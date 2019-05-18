<?php

/**
 * @file
 * Contains \Drupal\botscout\Plugin\Block\BotscoutBlock.
 */

namespace Drupal\botscout\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Protected by BotScout' block.
 *
 * @Block(
 *   id = "botscout_block",
 *   admin_label = @Translation("BotScout Footer")
 * )
 */
class BotscoutBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $footer = \Drupal::config('botscout.settings')->get('botscout_footer');
    if($footer == TRUE){
      return array(
        '#type' => 'markup',
        '#markup' => '<span>' . $this->t('Protected by <a href=":protectedby" target="_blank">BotScout</a>', array(':protectedby' => 'http://www.botscout.com')) . '</span>',
        '#region' => 'footer_fifth',
      );
    }
  }
}
