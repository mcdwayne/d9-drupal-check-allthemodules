<?php

/**
 * @file
 * Contains \Drupal\crossdomain\Controller\CrossdomainController.
 */

namespace Drupal\crossdomain\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for path routes.
 */
class CrossdomainController extends ControllerBase {

  public function view() {
    // @TODO: Need to get the values from the db and render
    //$domains = variable_get('crossdomain', array());

    $xml = "<?xml version=\"1.0\"?>\n
<cross-domain-policy>\n";

    $domains = $this->entityManager()->getStorageController('crossdomain')->loadMultiple();
    foreach ($domains as $domain) {
      $xml .= '  <allow-access-from domain="' . $domain->label() . '" />' . "\n";
    }
    $xml .= '</cross-domain-policy>';

    $headers = array(
      'Content-Length' => strlen($xml),
      'Content-Type' => 'text/xml'
    );
    return new Response($xml, 200, $headers);
  }
}
