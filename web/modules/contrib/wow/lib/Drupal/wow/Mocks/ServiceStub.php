<?php

/**
 * @file
 * Service stub.
 */

namespace Drupal\wow\Mocks;


use WoW\Core\Request;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;
use WoW\Core\Service\ServiceHttp;

/**
 * Service stub.
 *
 * Instead of calling the service API, it takes directly from file system.
 */
class ServiceStub extends ServiceHttp {

  public function __construct() {
    parent::__construct('local', array());
  }

  public function request($path, array $query = array(), array $headers = array()) {
    $base = drupal_get_path('module', 'wow');
    // Adds a default locale here.
    $query += array('locale' => 'en_GB');
    $filename = "$base/tests/resources/{$query['locale']}/$path.json";

    // Creates an empty response (simulates drupal_http_request).
    $response = (object) array(
      'protocol' => '',
      'request' => '',
      'headers' => array('date' => date("D, d M Y H:i:s T")),
    );

    // Checks in the file system if the file exists.
    if (file_exists($filename)) {
      // The file has been found.
      $response->data = file_get_contents($filename);
      $response->code = 200;
    }
    else {
      // Returns a 404 Not Found.
      $response->data = '{"status":"nok", "reason":"Not found."}';
      $response->code = 404;
    }

    return new Response($response);
  }
}
