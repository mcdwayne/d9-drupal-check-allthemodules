<?php
/**
 * Created by PhpStorm.
 * User: piyuesh23
 * Date: 10/08/14
 * Time: 9:05 PM
 */

namespace Drupal\social_stats;


class SocialStatsTwitterManager extends SocialStatsManagerBase {
  /**
   * @return int
   *
   * Processes the stats response from twitter API & saves it in the database.
   */
  public function processResponse() {
    $tweets_response = $this->response;
    $twitter_shares = 0;

    if (!empty($tweets_response['error'])) {
      \Drupal::logger('social_stats')->info('Problem updating data from Twitter for %node_path. Error: %err',
        array('%node_path' => $this->path, '%err' => $tweets_response['error']));
    }
    else {
      // Only update table if counter > 0
      $twitter_shares = intval($tweets_response['count']);
      if ($twitter_shares) {
        db_merge('social_stats_twitter')
          ->key(array('nid' => $this->nid))
          ->fields(array('tweets' => $twitter_shares))
          ->execute();
      }
    }

    return $twitter_shares;
  }
} 