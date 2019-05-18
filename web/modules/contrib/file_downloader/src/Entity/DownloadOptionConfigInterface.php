<?php

namespace Drupal\file_downloader\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;

/**
 * Provides an interface for defining Download option config entities.
 */
interface DownloadOptionConfigInterface extends ConfigEntityInterface {

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\file_downloader\DownloadOptionPluginInterface
   *   The plugin instance for this download option configuration.
   */
  public function getPlugin();

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this download option configuration.
   */
  public function getPluginId();

  /**
   * Returns the string with allowed extensions
   *
   * @return string
   *  The allowed extensions for this download option configuration.
   */
  public function getExtensions();

  /**
   * Returns the collection of download option plugins
   *
   * @param \Drupal\file_downloader\Entity\DownloadOptionConfigInterface $downloadOptionConfig
   *
   * @return \Drupal\file_downloader\DownloadOptionPluginCollection
   */
  public function getPluginCollection(DownloadOptionConfigInterface $downloadOptionConfig);

  /**
   * Access callback to validate if the user has access to the download option links.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to validate access on.
   * @param \Drupal\file\FileInterface $file
   *   The file which is going to be downloaded.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  public function accessDownload(AccountInterface $account, FileInterface $file = NULL);

  /**
   * List of extensions allowed for use with this download options.
   *
   * @return array
   */
  public function getExtensionList();

  /**
   * Check if the file extensions are allowed for the download option config entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   File object to validate.
   *
   * @return bool|int
   */
  public function validFileExtensions(FileInterface $file);

}
