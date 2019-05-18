<?php

namespace Drupal\file_version;

/**
 * Interface FileVersionInterface.
 *
 * @package Drupal\file_version
 */
interface FileVersionInterface {

  /**
   * Implements the logic to when add file version token.
   *
   * @param string $uri
   *   Referenced uri to add the file version token.
   * @param string $original_uri
   *   Original uri, not modified by
   *   \Drupal\Core\StreamWrapper\StreamWrapperInterface::getExternalUrl().
   */
  public function addFileVersionToken(&$uri, $original_uri);

  /**
   * Method that parse a comma separated string to convert into an array.
   *
   * @param string $string
   *   Comma separated string list.
   *
   * @return array
   *   Array with items splitted by commas.
   */
  public function parseCommaSeparatedList($string);

  /**
   * Return file version token.
   *
   * @param string $uri
   *   Uri to calculate file version token.
   *
   * @return string
   *   Token calculated through the uri.
   */
  public function getFileVersionToken($uri);

  /**
   * Crypt data to get the final token.
   *
   * @param string $data
   *   String to be crypted.
   *
   * @return string
   *   Crypted string.
   */
  public function getCryptedToken($data);

  /**
   * Determine if the current protocol is by passed.
   *
   * @param string $protocol
   *   Protocol to check.
   *
   * @return bool
   *   Returns if the protocol is by passed or not.
   */
  public function isProtocolByPassed($protocol);

  /**
   * Define an invalid array list of query parameters for file version.
   *
   * This list must avoid drupal common params like q, file, etc.
   *
   * @return array
   *   List of query parameters.
   */
  public function getInvalidQueryParameterNames();

}
