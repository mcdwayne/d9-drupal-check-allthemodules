<?php

namespace Drupal\drd\Agent\Action;

/**
 * Defines an interface for Remote DRD Action Code.
 */
interface BaseInterface {

  /**
   * Change current session to user 1.
   */
  public static function promoteUser();

  /**
   * Get authorised Crypt object or FALSE if none is available.
   *
   * @param string $uuid
   *   UUID of the crypt instance that should be loaded.
   *
   * @return \Drupal\drd\Crypt\BaseMethodInterface|bool
   *   The loaded Crypt instance if available or FALSE otherwise.
   */
  public static function getCryptInstance($uuid);

  /**
   * Authorize the DRD instance, all validations have passed successfully.
   *
   * @param string $remoteSetupToken
   *   The token including settings.
   */
  public static function authorize($remoteSetupToken);

  /**
   * Get an array of database connection information.
   *
   * @return array
   *   The database connection information.
   */
  public static function getDbInfo();

  /**
   * Get the arguments for this request.
   *
   * @return array
   *   Normalised array of all arguments received with the request.
   */
  public static function getArguments();

  /**
   * Get the debug mode.
   *
   * @return bool
   *   TRUE if debug mode is active, FALSE otherwise.
   */
  public static function getDebugMode();

  /**
   * Set the debug mode.
   *
   * @var bool $debugMode
   *   TRUE if active, FALSE otherwise.
   */
  public static function setDebugMode($debugMode);

  /**
   * Logging if in debug mode.
   *
   * @param string $message
   *   Message of the watchdog report.
   * @param array $variables
   *   Parameters for the watchdog report.
   * @param int $severity
   *   Severity of the watchdog report.
   * @param string $link
   *   Optional link associated with the watchdog report.
   */
  public static function watchdog($message, array $variables = array(), $severity = 5, $link = NULL);

  /**
   * Validate a one-time-token.
   *
   * @param string $ott
   *   Token to be validated.
   * @param string $remoteSetupToken
   *   Base64 encoded RemoteSetupToken from DRD.
   *
   * @return bool
   *   TRUE if token is valid and configuration succeeded, FALSE otherwise.
   */
  public static function ott($ott, $remoteSetupToken);

  /**
   * Execute an action.
   *
   * @return array
   *   The response of the action as an array which will be encrypted before
   *   returned to DRD.
   */
  public function execute();

}
