<?php

namespace Drupal\qwantsearch\Service;

/**
 * Interface QwantSearchInterface.
 *
 * @package Drupal\qwantsearch
 */
interface QwantSearchInterface {

  /**
   * Makes a query to Qwant using a httpToken, partner_id and query_text.
   *
   * @param array $params
   *   Parameters for the query.
   *
   * @return object
   *   Object returned by json_decode.
   */
  public function makeQuery(array $params = []);

  /**
   * Return TRUE if status is a success.
   *
   * @param object $response
   *   Response returned by makeQuery.
   *
   * @return bool
   *   TRUE if request succeeded, FALSE otherwise.
   */
  public function isSuccess($response);

}
