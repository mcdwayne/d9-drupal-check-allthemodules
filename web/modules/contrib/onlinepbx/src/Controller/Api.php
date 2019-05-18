<?php

namespace Drupal\onlinepbx\Controller;

/**
 * @file
 * Contains \Drupal\synhelper\Controller\Page.
 */
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class Api extends ControllerBase {

  /**
   * Test.
   */
  public static function callNow($to, $from = FALSE) {
    $config = \Drupal::config('onlinepbx.settings');
    if (!$from) {
      $from = $config->get('from');
    }
    \Drupal::moduleHandler()->alter('onlinepbx_from', $from);
    $otvet = "Call Now\n$from => $to\n";
    $data = [
      "from" => $from,
      "to" => $to,
      "from_orig_name" => 'web-site',
    ];
    \Drupal::logger('onlinepbx')->notice("call: $from => $to");
    // Get call history.
    $result = self::request("call/now.json", $data, TRUE);
    $otvet .= print_r($result, TRUE);
    return $otvet;
  }

  /**
   * Test.
   */
  public static function test() {
    $otvet = "callHistory\n-1 day => now\n";
    $data = [
      "date_from" => (new \DateTime())->modify("-1 day")->format("r"),
      "date_to" => (new \DateTime())->format("r"),
    ];
    // Get call history.
    $result = self::request("history/search.json", $data);
    $otvet .= print_r($result, TRUE);
    return $otvet;
  }

  /**
   * Test.
   */
  public static function request($method, $data, $noResponse = FALSE) {
    $config = \Drupal::config('onlinepbx.settings');
    $url = $config->get('url');
    $key = $config->get('key');
    // Create new client object.
    $client = new ApiClient($url, $key, FALSE, $noResponse);
    $result = $client->sendRequest($method, $data);
    return $result;
  }

  /**
   * API is OK.
   */
  public static function isOk($data) {
    $result = FALSE;
    if (isset($data['status']) && $data['status'] == 1) {
      $result = $data['data'];
    }
    return $result;
  }

}
