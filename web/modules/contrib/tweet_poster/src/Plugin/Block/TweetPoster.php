<?php /**
 * @file
 * Contains \Drupal\tweet_poster\Plugin\Block\TweetPoster.
 */

namespace Drupal\tweet_poster\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides the TweetPoster block.
 *
 * @Block(
 *   id = "tweet_poster_tweet_poster",
 *   admin_label = @Translation("Tweet Poster")
 * )
 */
class TweetPoster extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /**
     * @FIXME
     * hook_block_view() has been removed in Drupal 8. You should move your
     * block's view logic into this method and delete tweet_poster_block_view()
     * as soon as possible!
     */
    return tweet_poster_block_view('tweet_poster');
  }

  
}
