<?php
/**
 * Created by PhpStorm.
 * User: ruth
 * Date: 2019-03-28
 * Time: 00:41
 */

namespace Drupal\google_calendar;

interface GoogleCalendarSecretsFileInterface {
  /**
   * Return the filename of the JSON Secrets file.
   *
   * @return string
   */
  public function getFilePath();

  /**
   * Get the secret file JSON from the JSON file from Google.
   *
   * @return bool|mixed
   *   A JSON array if the file was loaded and parsed ok, otherwise FALSE if
   *   no file has been set or the JSON was missing/invalid.
   *
   * @throws \Drupal\google_calendar\GoogleCalendarSecretsException
   *   If the secrets file config is not valid, or the file could not be opened.
   */
  public function get();
}