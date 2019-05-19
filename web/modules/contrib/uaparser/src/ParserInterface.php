<?php

namespace Drupal\uaparser;

/**
 * Defines the ParserInterface for classes to integrate with ua-parser.
 */
interface ParserInterface {

  /**
   * Updates the user-agent definition file.
   *
   * The Drupal ua-parser module integrates Drupal with the ua-parser/uap-php
   * library; this function updates the user-agent definitions file from the
   * latest version available in the ua-parser/uap-core repository.
   *
   * @param bool $set_message
   *   (Optional) If TRUE, errors are presented to admins via
   *   drupal_set_message. Defaults to FALSE.
   *
   * @return bool
   *   TRUE if update was successful, FALSE if any error occurred.
   *
   * @see https://github.com/ua-parser/uap-php
   * @see https://github.com/ua-parser/uap-core
   */
  public function update($set_message = FALSE);

  /**
   * Parses an user-agent string.
   *
   * @param string $ua
   *   The user-agent string to be parsed.
   *   drupal_set_message.
   * @param bool $use_cache
   *   (Optional) If TRUE, parsed results are cached to an uaparser cache bin
   *   to speed up further resolution.
   *
   * @return array
   *   An associative array, containing one or more of the following keys:
   *   'client' - a \UAParser\Result\Client object as returned by
   *              ua-parser/uap-php.
   *   'error'  - a string with the message of an error occurred during the
   *              parsing (if any).
   *   'time'   - a float indicating the time it took to parse the user-agent
   *              string (if not taken from cache).
   */
  public function parse($ua, $use_cache = TRUE);

}
