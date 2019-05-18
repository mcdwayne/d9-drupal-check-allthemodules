<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'Library' code.
 */
class Library extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $uri = file_directory_temp() . '/' . DRD_ARCHIVE;
    $args = $this->getArguments();
    if (isset($args['lib'])) {
      $data = base64_decode($args['lib']);
    }
    else {
      $response = drupal_http_request($args['url']);
      if (empty($response->code) || $response->code != 200) {
        throw new \Exception('DRD Library not available');
      }
      $data = $response->data;
    }
    file_put_contents($uri, $data);

    return array();
  }

}
