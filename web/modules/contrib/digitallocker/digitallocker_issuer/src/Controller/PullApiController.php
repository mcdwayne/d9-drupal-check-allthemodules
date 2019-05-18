<?php

namespace Drupal\digitallocker_issuer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Url;
use Drupal\digitallocker_issuer\SignedPdf;
use SimpleXMLElement;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PullApiController.
 *
 * @package Drupal\digitallocker_issuer\Controller
 */
class PullApiController extends ControllerBase {

  /**
   * Return the signed document corresponding to the url specified.
   */
  public function fetchDocument() {

    $input = file_get_contents('php://input');
    if (empty($input)) {
      throw new NotFoundHttpException('No input');
    }

    $request = new SimpleXMLElement($input);
    $attr = $request->attributes();

    $alias = \Drupal::service('path.alias_manager')
      ->getPathByAlias($request->DocDetails->URI);
    $params = Url::fromUri('internal:/' . $alias)->getRouteParameters();
    $entity_type = key($params);
    $node = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->load($params[$entity_type]);
    if (!$node) {
      throw new NotFoundHttpException("url {$request->DocDetails->URI} does not correspond to a certificate.");
    }

    $response = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" standalone="yes"?><PullDocResponse xmlns:ns2="http://tempuri.org/"/>');
    $response->ResponseStatus = NULL;
    $response->ResponseStatus->addAttribute('Status', 1);
    $response->ResponseStatus->addAttribute('ts', $attr['ts']);
    $response->ResponseStatus->addAttribute('txn', $attr['txn']);
    $response->DocDetails->DocContent = base64_encode(SignedPdf::generate($node));
    return new HtmlResponse($response->asXML());
  }

}
