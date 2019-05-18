<?php

namespace Drupal\rst;

use Drupal;
use Exception;
use Gregwar\RST\ErrorManager;

/**
 * Overrides the Gregwar\RST\ErrorManager class for a finer handling of RST
 * parsing errors, better integrated within the Drupal ecosystem.
 *
 * @package Drupal\rst
 */
class RSTErrorManager extends ErrorManager {
  protected $abort = FALSE;
  protected $raiseWarnings = FALSE;
  protected $collectErrors = FALSE;
  protected $logErrors = FALSE;
  protected $errorList = [];

  /**
   * Defines the 'abortOnError' option.
   *
   * @param boolean $abortOnErrors
   *   Whether the RST parser should raise an Exception if an error occurs.
   */
  public function abortOnError($abortOnErrors) {
    $this->abort = $abortOnErrors;
  }

  /**
   * Defines the 'raiseWarningsOnError' option.
   *
   * If set to TRUE, all errors will raise Drupal warnings.
   * Does not apply if abortOnError was set to TRUE.
   *
   * @param boolean $raiseWarnings
   *   Whether the RST parser should raise Drupal warnings if errors occur.
   */
  public function raiseWarningsOnError($raiseWarnings) {
    $this->raiseWarnings = $raiseWarnings;
  }

  /**
   * Defines the 'collectErrors' option.
   *
   * If set to TRUE, all errors will get collected in memory so they can be
   * retrieved with $this->getErrors() after a parsing.
   *
   * @param boolean $collectErrors
   *   Whether the RST parser should collect errors if any.
   */
  public function collectErrors($collectErrors) {
    $this->collectErrors = $collectErrors;
  }

  /**
   * Defines the 'logErrors' option.
   *
   * If set to TRUE, all parsing errors will get logged as warnings in Drupal's
   * watchdog.
   *
   * @param boolean $logErrors
   *   Whether the RST parser should collect errors if any.
   */
  public function logErrors($logErrors) {
    $this->logErrors = $logErrors;
  }

  /**
   * Raises a parsing error.
   *
   * @param string $message
   *   The error message.
   *
   * @throws \Exception
   */
  public function error($message) {
    // Log error if required.
    if ($this->logErrors) {
      Drupal::logger('rst')->warning("Error while trying to apply the ReStructuredText filter: '$message'.");
    }

    // Collect error if required.
    if ($this->collectErrors) {
      $this->errorList[] = $message;
    }

    // Raise an exception if required.
    if ($this->abort) {
      throw new Exception($message);
    }

    // Raise warning if required.
    if ($this->raiseWarnings) {
      drupal_set_message(t('ReStructuredText parsing error: @msg', ['@msg' => $message]), 'warning');
    }
  }

  /**
   * Retrieves all collected errors.
   *
   * @return array
   *   List of errors that were encountered during parsing.
   */
  public function getErrorList() {
    return $this->errorList;
  }

  /**
   * Empties the current error list.
   */
  public function resetErrorList() {
    $this->errorList = [];
  }
}
