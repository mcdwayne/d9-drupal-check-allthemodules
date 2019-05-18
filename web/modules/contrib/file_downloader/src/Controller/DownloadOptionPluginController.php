<?php

namespace  Drupal\file_downloader\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\file_downloader\Entity\DownloadOptionConfigInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DownloadOptionPluginController
 *
 * @package Drupal\file_downloader\Controller
 */
class DownloadOptionPluginController {

  /**
   * Callback to download a file based on the given Download option Configuration.
   *
   * @param \Drupal\file_downloader\Entity\DownloadOptionConfigInterface $downloadOptionConfig
   *  Download option configuration for getting the plugin.
   * @param \Drupal\file\FileInterface $file
   *  File object which will be downloaded based on the plugin.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *  Contains the request from the route.
   */
  public function downloadFile(DownloadOptionConfigInterface $download_option_config, FileInterface $file, Request $request){
    $downloadOptionPlugin = $download_option_config->getPlugin();
    return $downloadOptionPlugin->deliver($file);
  }

  /**
   * Access callback to validate if the user has access to the download option links.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to validate access on.
   * @param DownloadOptionConfigInterface $download_option_config
   *   The download option config entity the download link belongs to.
   * @param \Drupal\file\FileInterface $file
   *   The file which is going to be downloaded.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  public function access(AccountInterface $account, DownloadOptionConfigInterface $download_option_config, FileInterface $file) {
    $result = $download_option_config->accessDownload($account, $file);

    return $result->isNeutral() ? AccessResult::allowed() : $result;
  }

}
