<?php

namespace Drupal\shopify\Controller;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class ShopifyThemeDownload.
 *
 * Provides route/access/download functionality for the theme archive.
 */
class ShopifyThemeDownload extends ControllerBase {

  /**
   * Download callback.
   *
   * @param int $timestamp
   *   Timestamp of the created request.
   * @param string $sig
   *   Secure signature that we can validate against.
   * @param string $file
   *   The file name.
   *
   * @return BinaryFileResponse
   *   BinaryFileResponse.
   */
  public function download($timestamp, $sig, $file) {
    $directory = realpath(file_directory_temp()) . '/shopify_default_theme_' . $timestamp;
    return self::downloadTheme($directory . '/' . $file);
  }

  /**
   * Ensure the user can access this file download.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   * @param int $timestamp
   *   Timestamp of the created request.
   * @param string $sig
   *   Secure signature that we can validate against.
   * @param string $file
   *   The file name.
   *
   * @return \Drupal\Core\Access\AccessResultReasonInterface
   *   AccessResult.
   */
  public function access(AccountInterface $account, $timestamp, $sig, $file) {
    $config = \Drupal::config('shopify_api.settings');
    $compare = hash_hmac('sha256', $timestamp . $file, $config->get('shared_secret'));
    if ($compare !== $sig) {
      // Someone doesn't have the right sig here.
      return new AccessResultForbidden();
    }
    if (\Drupal::time()->getRequestTime() > $timestamp + 500) {
      // The link has expired.
      return new AccessResultForbidden();
    }
    return new AccessResultAllowed();
  }

  /**
   * Downloads the theme files to the local client.
   *
   * @param string $filepath
   *   File path.
   *
   * @return BinaryFileResponse
   *   Response.
   */
  public static function downloadTheme($filepath) {
    return new BinaryFileResponse($filepath);
  }

}
