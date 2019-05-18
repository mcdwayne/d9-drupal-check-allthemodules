<?php

/**
 * @file
 * Contains \Drupal\email_verify\EmailVerifyManagerInterface.
 */

namespace Drupal\email_verify;

/**
 * Provides an interface defining an email verify manager.
 */
interface EmailVerifyManagerInterface {

  /**
   * Runs a connection test against an email address.
   *
   * This will give an indication about whether the email address actually
   * exists.
   *
   * @param string $email
   *   The email address to check.
   *
   *
   * @return null
   *
   */
  public function checkEmail($email);

  /**
   * Runs a connection test against a host.
   *
   * This will give an indication about whether a host is a valid mail server.
   *
   * @param string $host
   *   The host address to check.
   *
   *
   * @return null
   */
  public function checkHost($host);

  /**
   * Make a connection to the mail server used by the host.
   *
   *This is found by checking MX records and connecting on port 25.
   *
   * @param string $host
   *   The host address to connect to.
   */
  function connect($host);

  /**
   * Sets error messages.
   *
   * Protected function to set error messages.
   */
  function setError($error);

  /**
   * Gets error messages.
   *
   * Public function to return the error messages.
   *
   *
   * @return array
   */
  public function getErrors();

}
