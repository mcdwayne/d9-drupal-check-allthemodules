<?php

namespace Drupal\acquia_contenthub\Plugin\FileSchemeHandler;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\file\FileInterface;

/**
 * Interface FileSchemeHandlerManagerInterface.
 *
 * @package Drupal\acquia_contenthub\Plugin\FileSchemeHandler
 */
interface FileSchemeHandlerManagerInterface extends PluginManagerInterface {

  /**
   * Returns file scheme handler.
   *
   * @param \Drupal\file\FileInterface $file
   *   File.
   *
   * @return FileSchemeHandlerInterface
   *   File scheme handler.
   */
  public function getHandlerForFile(FileInterface $file);

}
