<?php

namespace Drupal\httpbl\Logger;

/**
 * (An arbitrated logger)
 *
 * HttpblLogTrapperInterface mimics LoggerInterface, adding a param that filters
 * messages according to the Httpbl config setting for log volume ($logVolume).
 * This allows verbose logging to be unconditionally added as needed, but then
 * only passed on to the actual logger when the settings conditions apply.
 *
 * The $logVolume param is NOT passed on when actual logging occurs.
 *
 * The functions are all based on log levels compliant to RFC 5424 integers:
 *
 * EMERGENCY = 0, ALERT = 1, CRITICAL = 2, ERROR = 3, WARNING = 4, NOTICE = 5,
 * INFO = 6, DEBUG = 7.
 * @see \Drupal\Core\Logger\RfcLogLevel
 *
 * Log volume filtering scheme:
 *   HTTPBL_LOG_QUIET   = LogVolume 0 - Quiet   -  Passes levels 0 -- 3
 *   HTTPBL_LOG_MIN     = LogVolume 1 - Minimal -  Passes levels 0 -- 5
 *   HTTPBL_LOG_VERBOSE = LogVolume 2 - Verbose -  Passes levels 0 -- 7
 *
 * @ingroup httpbl_api
 */

interface HttpblLogTrapperInterface {

  /**
   * Traps and forwards EMERGENCY messages, based on config setting.
   *
   * @param string $message
   * @param array $context
   * @param integer $logVolume,
   *   The minimum setting required to pass on the message.
   * @return null
   */
  public function trapEmergency($message, array $context = array(), $logVolume = HTTPBL_LOG_QUIET);

  /**
   * Traps and forwards ALERT messages, based on config setting.
   *
   * @param string $message
   * @param array $context
   * @param integer $logVolume,
   *   The minimum setting required to pass on the message.
   * @return null
   */
  public function trapAlert($message, array $context = array(), $logVolume = HTTPBL_LOG_QUIET);

  /**
   * Traps and forwards CRITICAL messages, based on config setting.
   *
   * @param string $message
   * @param array $context
   * @param integer $logVolume,
   *   The minimum setting required to pass on the message.
   * @return null
   */
  public function trapCritical($message, array $context = array(), $logVolume = HTTPBL_LOG_QUIET);

  /**
   * Traps and forwards ERROR messages, based on config setting.
   *
   * @param string $message
   * @param array $context
   * @param integer $logVolume,
   *   The minimum setting required to pass on the message.
   * @return null
   */
  public function trapError($message, array $context = array(), $logVolume = HTTPBL_LOG_QUIET);

  /**
   * Traps and forwards WARNING messages, based on config setting.
   *
   * @param string $message
   * @param array $context
   * @param integer $logVolume,
   *   The minimum setting required to pass on the message.
   * @return null
   */
  public function trapWarning($message, array $context = array(), $logVolume = HTTPBL_LOG_MIN);

  /**
   * Traps and forwards NOTICE messages, based on config setting.
   *
   * @param string $message
   * @param array $context
   * @param integer $logVolume,
   *   The minimum setting required to pass on the message.
   * @return null
   */
  public function trapNotice($message, array $context = array(), $logVolume = HTTPBL_LOG_MIN);

  /**
   * Traps and forwards INFO messages, based on config setting.
   *
   * @param string $message
   * @param array $context
   * @param integer $logVolume,
   *   The minimum setting required to pass on the message.
   * @return null
   */
  public function trapInfo($message, array $context = array(), $logVolume = HTTPBL_LOG_VERBOSE);

  /**
   * Traps and forwards DEBUG messages, based on config setting.
   *
   * @param string $message
   * @param array $context
   * @param integer $logVolume,
   *   The minimum setting required to pass on the message.
   * @return null
   */
  public function trapDebug($message, array $context = array(), $logVolume = HTTPBL_LOG_VERBOSE);

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array());

}
