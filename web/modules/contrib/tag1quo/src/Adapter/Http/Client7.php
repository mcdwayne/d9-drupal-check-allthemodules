<?php

namespace Drupal\tag1quo\Adapter\Http;

/**
 * Class Client7.
 *
 * @internal This class is subject to change.
 */
class Client7 extends Client {

  /**
   * {@inheritdoc}
   */
  protected function prepareRequest(Request $request) {
    parent::prepareRequest($request);
    $request->options->set('data', $request->options->get('body'));
    $request->options->set('method', $request->getMethod());
  }

  /**
   * {@inheritdoc}
   */
  protected function doRequest(Request $request) {
    $result = \drupal_http_request($request->getUri(), $request->getOptions());
    return $this->createResponse($result->data, $result->code, $result->headers);
  }

}
