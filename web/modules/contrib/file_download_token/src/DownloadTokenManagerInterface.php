<?php

namespace Drupal\file_download_token;

use Drupal\file\FileInterface;

interface DownloadTokenManagerInterface {

  /**
   * @param \Drupal\file\FileInterface $file
   *
   * @return string
   */
  public function createToken(FileInterface $file);

  /**
   * @param \Drupal\file\FileInterface $file
   *
   * @return \Drupal\Core\Url
   */
  public function createTokenUrl(FileInterface $file);

  /**
   * @param \Drupal\file\FileInterface $file
   *
   * @return \Drupal\Core\Link
   */
  public function createTokenLink(FileInterface $file);

  /**
   * @return string
   */
  public function generateToken();

  /**
   * @param string $token
   *
   * @return FileInterface
   */
  public function getFile(string $token);

  /**
   * We delete all tokens that are no longer valid.
   */
  public function cleanupTokens();

}