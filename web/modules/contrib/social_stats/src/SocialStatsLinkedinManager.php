<?php
/**
 * Created by PhpStorm.
 * User: piyuesh23
 * Date: 10/08/14
 * Time: 9:10 PM
 */

namespace Drupal\social_stats;


class SocialStatsLinkedinManager extends SocialStatsManagerBase {
  /**
   * {@inheritdoc}
   */
  public function processResponse() {
    $linkedin_shares = 0;
    $linkedin_response = $this->response;

    if (!empty($linkedin_response['error'])) {
      \Drupal::logger('social_stats')->info('Problem updating data from LinkedIn for %node_path. Error: %err',
        array('%node_path' => $node_path, '%err' => $linkedin_response['error']));
    }
    else {
      // Only update table if counter > 0
      $linkedin_shares = intval($linkedin_response['count']);
      if ($linkedin_shares) {
        db_merge('social_stats_linkedin')
          ->key(array('nid' => $node->nid))
          ->fields(array('shares' => $linkedin_shares))
          ->execute();
      }
    }
    return $linkedin_shares;
  }
}