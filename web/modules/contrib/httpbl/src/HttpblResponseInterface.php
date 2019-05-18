<?php

namespace Drupal\httpbl;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Provides an interface defining the HttpblResponse manager.
 *
 * @ingroup httpbl_api
 */
interface HttpblResponseInterface {

  /**
   * Build a new HttpblResponse based on evaluation status.
   *
   * @param string                                           $ip
   *   The IP address to evaluate.
   *
   * @param object|\Symfony\Component\HttpFoundation\Request $request
   *   The incoming http request.
   *
   * @param object|\Symfony\Component\HttpKernel\HttpKernel $defaultResponse
   *   The default http response.
   *
   * @param array                                            $evaluated
   *   Contains a boolean of whether the evaluation has happened, and the
   *  resulting evaluated status.
   *
   * @return object $httpblResponse
   *   A rebuilt response.
   */
  public function buildHttpblResponse($ip, SymfonyRequest $request, $defaultResponse, $evaluated = NULL);

  /**
   * Build a response to a white-list challenge failure.
   *
   * This is the response sent when local blacklisting is possible with storage
   * enabled.
   *
   * @param string                                           $ip
   *   The IP address to evaluate.
   * @param string                                           $return_date
   *   A date formatted as time hence.
   * @return object $failureResponse
   *   A PRECONDITION FAILED  (412) response.
   */
  public function challengeFailureBlacklisted($ip, $return_date);

  /**
   * Build a response to a white-list challenge failure.
   *
   * Simpler response because we cannot blacklist them if not configed for
   * storage.  The visitor will be continually challenged on each visit,
   * until a success occurs for session based white-listed.
   *
   * @return object $failureResponse
   *   A PRECONDITION FAILED  (412) response.
   */
  public function challengeFailurePurgatory();

  /**
   * Create a (standalone) Honeypot link.
   *
   * @return string $honeypot
   */
  public static function buildHoneypot();

  /**
   * Return HTML code with hidden Honeypot link
   * in one of several styles.
   *
   * @param string $link
   *   the link to a honeypot script.
   *
   * @param string $word
   *   the text of the link.
   *
   * @return string
   *   The formatted link.
   */
  public static function httpbl_honeylink($link, $word);

}
