<?php

namespace Drupal\style_management\Controller;

/**
 * Interface FileControllerInterface.
 *
 * @package Drupal\style_management\Controller
 */
interface FileControllerInterface {

  /**
   * Check if current file is processable.
   *
   * @param array $config
   *   All config info for this item.
   * @param string $path
   *   All info for current element.
   *
   * @return mixed
   *   Return Altered Config.
   */
  public function isProcessable(array &$config, string $path);

  /**
   * Write files, the array have info and content.
   *
   * @param array $files
   *   The array of files to create.
   *
   * @return bool
   *   The state of writing action.
   */
  public function writeFiles(array $files);

}
