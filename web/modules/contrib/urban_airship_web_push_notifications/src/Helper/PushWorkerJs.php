<?php

namespace Drupal\urban_airship_web_push_notifications\Helper;

/**
 * Parse push-worker.js file
 */
class PushWorkerJs {

  /**
   * Parse push-worker.js to get all parameters.
   */
  public function parse() {
    $config = \Drupal::config('urban_airship_web_push_notifications.configuration');
    $push_worker_js = $config->get('push-worker.js');
    $parameters = [];
    if (preg_match_all("/\{[^\]]*\}/", $push_worker_js, $matches)) {
      $parameters = $this->decodeJson($matches[0][0]);
    }
    return $parameters;
  }

  /**
   * Decode JSON and wrap each parameters with quotes (").
   */
  protected function decodeJson($string) {
    $string = str_replace(['"',  "'"], ['\"', '"'], $string);
    $string = preg_replace('/(\n[\s||\t]*)(\w+):[\s||\t]/i', '$1"$2":', $string);
    return json_decode($string, TRUE);
  }

}
