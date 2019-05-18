<?php
/**
 * @file
 * Contains \Drupal\byu_news\Plugin\Block\ByuNewsBlock.
 */

namespace Drupal\byu_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides BYU News block.
 *
 * @Block(
 *   id = "byu_news_block",
 *   admin_label = @Translation("BYU News Block"),
 *   category = @Translation("Blocks")
 * )
 */
class ByuNewsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $category = 2975;
    $minDate = date("Y-m-d", strtotime("-1 month"));
    $maxDate = date("Y-m-d");
    $storyLimit = 3;
    $newsTags = "all";


    return [
      '#theme' => 'byu_news',
      '#category' => $category,
      '#maxDate' => $maxDate,
      '#minDate' => $minDate,
      '#storyLimit' => $storyLimit,
      '#newsTags' => $newsTags,
      '#attached' => [
        'library' => [
          'byu_news/byu_news',
          'byu_news/news-component-js',
          'byu_news/news-component-css'
        ]
      ]
    ];
  }
}
