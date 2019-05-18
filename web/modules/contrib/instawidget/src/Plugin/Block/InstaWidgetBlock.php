<?php

/**
 * @file
 * Contains \Drupal\instawidget\Plugin\Block\InstaWidgetBlock.
 */

namespace Drupal\instawidget\Plugin\Block;

use Drupal\Core\Block\BlockBase;


/**
 * Provides a 'instafeeds page' block.
 *
 * @Block(
 *   id = "instafeeds_block",
 *   admin_label = @Translation("Instagram Feeds Block"),
 *   category = @Translation("Instagram Feeds Block")
 * )
 */
class InstaWidgetBlock extends BlockBase {
/**
   * {@inheritdoc}
   */
  
  public function build() {
  $build = [
      '#theme' => 'instawidget',
      '#cache' => ['max-age' => 0],
         '#attached' => [
            'library' => [
             'instawidget/instawidgetblock',
             ],
           ],
    ];
   
    return $build;

  }
    
    
}