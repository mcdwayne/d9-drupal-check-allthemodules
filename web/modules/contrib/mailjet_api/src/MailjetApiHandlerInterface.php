<?php

namespace Drupal\mailjet_api;

/**
 * Mail handler to send out an email message array to the Mailjet API.
 */
interface MailjetApiHandlerInterface {

  /**
   * Connects to Mailjet API and sends out the email.
   *
   * @param array $body
   *   A messages body array, as described in
   *   https://dev.mailjet.com/guides/#send-api-v3-1.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted by the API, FALSE otherwise.
   */
  public function sendMail(array $body);

  /**
   * Check Mailjet library and API settings.
   *
   * @param bool $showMessage
   *   Display message error.
   *
   * @return bool
   *   Return TRUE if library and API settings are correctly set.
   */
  public static function status($showMessage = FALSE);

  /**
   * Check that Mailjet API PHP SDK is installed correctly.
   *
   * @param bool $showMessage
   *   Display message error.
   *
   * @return bool
   *   Return TRUE if library is correctly installed.
   */
  public static function checkLibrary($showMessage = FALSE);

  /**
   * Check if API settings are correct and not empty.
   *
   * @param bool $showMessage
   *   Display message error.
   *
   * @return bool
   *   Return TRUE if API settings are correctly configured.
   */
  public static function checkApiSettings($showMessage = FALSE);

  /**
   * Validates Mailjet API key.
   *
   * @param string $api_key_public
   *   The public api key.
   * @param string $api_key_secret
   *   The secret api key.
   *
   * @return bool
   *   Return TRUE if API key are valid.
   */
  public static function validateKey($api_key_public, $api_key_secret);

  /**
   * Build the body message attended by Mailjet API..
   *
   * @param array $message
   *   The standard Drupal message array.
   *
   * @return array
   *   A messages body array, as described in
   *   https://dev.mailjet.com/guides/#send-api-v3-1.
   */
  public function buildMessagesBody(array $message);

}
