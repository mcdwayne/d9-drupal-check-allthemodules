<?php

namespace Drupal\drd\Agent\Action\V8;

use GuzzleHttp\Client;

/**
 * Provides a 'Library' code.
 */
class Library extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $uri = 'temporary://' . DRD_ARCHIVE;
    $args = $this->getArguments();
    if (isset($args['lib'])) {
      $data = base64_decode($args['lib']);
    }
    else {
      try {
        $client = new Client(['base_uri' => $args['url']]);
        $response = $client->request('get');
      }
      catch (\Exception $ex) {
        throw new \Exception('DRD Library not available');
      }
      if ($response->getStatusCode() != 200) {
        throw new \Exception('DRD Library not available');
      }
      $data = $response->getBody()->getContents();
    }
    file_put_contents($uri, $data);

    return [];
  }

}
