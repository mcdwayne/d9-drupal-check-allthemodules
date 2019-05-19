<?php
/**
 * Created by PhpStorm.
 * User: piyuesh23
 * Date: 10/08/14
 * Time: 9:10 PM
 */

namespace Drupal\social_stats;


class SocialStatsGplusManager extends SocialStatsManagerBase {
  
  public function execute() {
    $this->processResponse();
  }
  public function processResponse() {
    $node_path = $this->path;
    $nid = $this->nid;
    $gplusLikeCount = $this->socialStatsGplusPlusone($node_path);
    $gplusShareCount = $this->socialStatsGplusShare($node_path);
    $gplusTotalCount = $gplusLikeCount + $gplusShareCount;

    // Only update table if counter > 0
    if ($gplus_count) {
      db_merge('social_stats_gplus')
        ->key(array('nid' => $nid))
        ->fields(
          array(
            'plusone' => $gplusPlusOne,
            'shares' => $gplusShareCount,
            'total' => $gplusTotalCount,
          )
        )
        ->execute();
    }
    return $gplusTotalCount;
  }
  public function socialStatsGplusPlusone($node_path) {
    $gplusPlusOne = 0;

    // Build the JSON data for the API request.
    $data['method'] = 'pos.plusones.get';
    $data['id'] = 'p';
    $data['params']['nolog'] = TRUE;
    $data['params']['id'] = $node_path;
    $data['params']['source'] = 'widget';
    $data['params']['userId'] = '@viewer';
    $data['params']['groupId'] = '@self';
    $data['jsonrpc'] = '2.0';
    $data['key'] = 'p';
    $data['apiVersion'] = 'v1';

    $url = 'https://clients6.google.com/rpc?key=AIzaSyCKSbrvQasunBoV16zDH9R33D88CeLr9gQ';
    $options['data'] = json_encode($data);
    $options['method'] = 'POST';
    $options['headers']['Content-Type'] = 'application/json';

    $request = Drupal::httpClient()->post($url, $options);

    try {
      $gplusPlusoneResponse = $request->json();
    }
    catch (RequestException $e) {
      watchdog_exception('social_stats', $e);
    }

    if (!empty($gplusPlusoneResponse['error']) || empty($gplusPlusoneResponse['data'])) {
      \Drupal::logger('social_stats')->info('Problem updating data from Google+ for %node_path. Error: %err',
        array('%node_path' => $node_path, '%err' => $gplusPlusoneResponse['error']));
    }
    else {
      if (isset($request['data']['result']['metadata']['globalCounts']['count'])) {
        $gplusPlusOne = intval($request['data']['result']['metadata']['globalCounts']['count']);
      }
    }
    return $gplusPlusOne;
  }
  public function socialStatsGplusShare($node_path) {
    $gplusShareCount = 0;

    $data = "f.req=%5B%22" . $node_path . "%22%2Cnull%5D&";
    $url = "https://plus.google.com/u/0/ripple/update";

    $options = array(
      'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
      'data' => $data,
      'method' => 'POST',
    );
    $request = Drupal::httpClient()->post($url, $options);

    try {
      $gplusShareResponse = $request->json();
    }
    catch (RequestException $e) {
      watchdog_exception('social_stats', $e);
    }

    $gplusShareResponse['data'] = substr($gplusShareResponse['data'], 6);
    $gplusShareResponse['data'] = str_replace(",,", ",null,", $gplusShareResponse['data']);
    $gplusShareResponse['data'] = str_replace(",,", ",null,", $gplusShareResponse['data']);
    $result = $gplusShareResponse['data'];

    $gplusShareCount = $result[0][1][4];
    return $gplusShareCount;
  }
}
