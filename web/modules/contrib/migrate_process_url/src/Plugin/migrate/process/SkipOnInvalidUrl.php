<?php


namespace Drupal\migrate_process_url\Plugin\migrate\process;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\process\SkipOnEmpty;
use Drupal\Component\Utility\UrlHelper;

/**
 * Checks if the input is a valid URL, throws a skip exception if it's not.
 *
 * This process plugin allows you to skip the process (or row) if the passed in
 * value is not valid.
 *
 * @code
 * field_my_website:
 *  -
 *    plugin: skip_on_invalid_url
 *    source: my_custom_url_source
 *  -
 *    plugin: field_link_generate
 *    title_source: my_custom_title_source
 *  -
 *    plugin: field_link
 *    uri_scheme: 'http://'
 * @endcode
 *
 * You can specify the method like you would any skip_on_* plugin:
 *
 * @code
 * field_my_website:
 *  -
 *    plugin: skip_on_invalid_url
 *    source: my_custom_url_source
 *    method: process
 *  -
 *    plugin: field_link_generate
 *    title_source: my_custom_title_source
 *  -
 *    plugin: field_link
 *    uri_scheme: 'http://'
 * @endcode
 *
 * If you only want to validate absolute URLs -- ones starting with scheme such
 * as "http" -- use the "absolute" key:
 *
 * @code
 * field_my_website:
 *  -
 *    plugin: skip_on_invalid_url
 *    source: my_custom_url_source
 *    method: process
 *    absolute: true
 *  -
 *    plugin: field_link_generate
 *    title_source: my_custom_title_source
 *  -
 *    plugin: field_link
 *    uri_scheme: 'http://'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "skip_on_invalid_url"
 * )
 */
class SkipOnInvalidUrl extends SkipOnEmpty {

  /**
   * {@inheritdoc}
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$this->isValid($value)) {
      $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      throw new MigrateSkipRowException($message);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$this->isValid($value)) {
      $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      throw new MigrateSkipProcessException($message);
    }

    return $value;
  }

  /**
   * Check the URL for validity.
   *
   * @param $uri
   *  The url to check.
   *
   * @return bool
   *   TRUE if the URL is valid, FALSE otherwise.
   */
  protected function isValid($uri) {
    // Empty values cannot be valid URLs.
    if (empty($uri)) {
      return FALSE;
    }

    // Allow us to check absolute URLs if so configured.
    $abs = empty($this->configuration['absolute']) ? FALSE : $this->configuration['absolute'];

    // Check the URL.
    $result = UrlHelper::isValid($uri, $abs);

    return $result;
  }

}
