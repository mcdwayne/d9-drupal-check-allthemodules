<?php

namespace Drupal\content_synchronizer\Service;

use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;

/**
 * Class ArchiveDownloader.
 *
 * @package Drupal\content_synchronizer\Service
 */
class ArchiveDownloader {

  const SERVICE_NAME = 'content_synchronizer.archive_downloader';

  const ARCHIVE_PARAMS = 'cs_archive';

  /**
   * Donwload archive by adding js library.
   *
   * @param array $vars
   *   Preprocess data.
   */
  public function donwloadArchive(array &$vars) {
    if ($this->canDownload()) {
      $vars['#attached']['library'][] = 'content_synchronizer/download_archive';
    }
  }

  /**
   * Return true if the current page is admin.
   *
   * @see https://drupal.stackexchange.com/questions/219370/how-to-test-if-current-page-is-an-admin-page
   *
   * @return bool
   *   True if can download.
   */
  public function canDownload() {
    $account = \Drupal::currentUser();
    return User::load($account->id())
      ->hasPermission('add export entity entities');
  }

  /**
   * Redirect to the page with download.
   *
   * @param string $redirectUrl
   *   The url.
   * @param string $archiveUri
   *   The archiev url.
   */
  public function redirectWithArchivePath($redirectUrl, $archiveUri) {
    $path = str_replace(ExportEntityWriter::GENERATOR_DIR, '', $archiveUri);
    $redirectUrl .= "#" . static::ARCHIVE_PARAMS . '=' . urlencode($path);

    $redirect = new RedirectResponse($redirectUrl);
    $redirect->send();
  }

}
