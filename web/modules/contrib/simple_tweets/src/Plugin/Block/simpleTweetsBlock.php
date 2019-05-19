<?php
/**
 * @file
 * Contains \Drupal\simple_tweets\Plugin\Block\simpleTweetsBlock.
 */

namespace Drupal\simple_tweets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'simple_tweets' block.
 *
 * @Block(
 *   id = "simple_tweets_block",
 *   admin_label = @Translation("Simple tweets"),
 *   category = @Translation("Forms")
 * )
 */
class simpleTweetsBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!empty(\Drupal::config('simple_tweets.settings')->get('simple_tweets_id'))) {
      return \Drupal::formBuilder()
          ->getForm('\Drupal\simple_tweets\Form\simpleTweetsBlockForm');
    }
    return array(
      '#markup' => $this->t('Unable to get the module settings'),
    );
  }

}
