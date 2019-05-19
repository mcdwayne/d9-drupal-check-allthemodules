<?php

/**
 * @file
 * Contains \Drupal\smartling\ApiWrapper\ApiWrapperInterface.
 */

namespace Drupal\smartling\ApiWrapper;

use Drupal\smartling\SmartlingSubmissionInterface;

/**
 * Provides an interface defining a smartling API wrapper.
 */
interface ApiWrapperInterface {

  /**
   * Defines XML type extension to send to remote.
   */
  const TYPE_XML = 'xml';

  /**
   * Defines GetText type extension to send to remote.
   */
  const TYPE_GETTEXT = 'gettext';

  /**
   * Returns Smartling API instance.
   *
   * @return \Drupal\smartling\Smartling\SmartlingApi
   *   Smartling API object from Smartling PHP SDK.
   *
   * @throws \Exception
   *   When API SDK class not found.
   */
  public function getApi();

  /**
   * Download file from service.
   *
   * @param \Drupal\smartling\SmartlingSubmissionInterface $submission
   *   Smartling transaction entity.
   *
   * @return string
   *   Return content of downloaded file.
   */
  public function downloadFile(SmartlingSubmissionInterface $submission);

  /**
   * Returns status of given entity's translation progress.
   *
   * @param \Drupal\smartling\SmartlingSubmissionInterface $submission
   *   Smartling transaction entity.
   *
   * @return array
   *   Array of remote status data.
   */
  public function getStatus(SmartlingSubmissionInterface $submission);

  /**
   * Test Smartling API instance init and connection to Smartling server.
   *
   * @param array $locales
   *   Keyed array of locales where key is Drupal language code.
   *
   * @return array
   *   Keyed array of locales where value is Drupal language code or FALSE when
   *   connection failed for the locale.
   */
  public function testConnection(array $locales);

  /**
   * Upload local file to Smartling for translation.
   *
   * @param string $file_path
   *   Real path to file.
   * @param string $file_name_unic
   *   Unified file name.
   * @param string $file_type
   *   File type. Use only 2 values 'xml' or 'getext'
   * @param array $locales
   *   List of locales in Drupal format.
   *
   * @return string
   *   SMARTLING_STATUS_EVENT_UPLOAD_TO_SERVICE | SMARTLING_STATUS_EVENT_FAILED_UPLOAD
   */
  public function uploadFile($file_path, $file_name_unic, $file_type, array $locales);

  /**
   * Gets list of locales for project.
   *
   * @return array
   *   An array of locales.
   */
  public function getLocaleList();

}
