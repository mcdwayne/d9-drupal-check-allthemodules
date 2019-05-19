<?php
/**
 * Created by PhpStorm.
 * User: piyuesh23
 * Date: 10/08/14
 * Time: 9:04 PM
 */

namespace Drupal\social_stats;

class SocialStatsFbManager extends SocialStatsManagerBase {
  /**
   * {@inheritdoc}
   */
  public function buildQueryUrl() {
    $fql_query  = 'SELECT share_count, like_count, commentsbox_count, total_count ';
    $fql_query .= "FROM link_stat WHERE url='" . $this->path . "'" ;

    // TODO: Implement multi-query
    // https://developers.facebook.com/docs/technical-guides/fql/
    $fql_queries = array('query1' => $fql_query);
    $params = array(
      'q' => json_encode($fql_queries),
      'format' => 'json',
    );

    $this->requestUrl = $this->baseUrl . http_build_query($params);
  }

  /**
   * {@inheritdoc}
   */
  public function processResponse() {
    $fql_response = $this->response;
    if (!empty($fql_response['error'])) {
      \Drupal::logger('social_stats')->info('Problem updating data from Facebook for %node_path. Error: %err',
        array('%node_path' => $this->path, '%err' => $fql_response['error']));
    }
    else {
      $fql_data = $fql_response['data'];
      foreach ($fql_data as $fql_data_row) {
        $facebook_data = array_shift($fql_data_row['fql_result_set']);
        // Only update table if counter > 0
        if (intval($facebook_data['total_count'])) {
          db_merge('social_stats_facebook')
            ->key(array('nid' => $this->nid))
            ->fields(array(
              'likes' => intval($facebook_data['like_count']),
              'shares' => intval($facebook_data['share_count']),
              'comments' => intval($facebook_data['commentsbox_count']),
              'total' => intval($facebook_data['total_count']),
            ))
            ->execute();
          return $facebook_data['total_count'];
        }
        return 0;
      }
    }
  }
} 