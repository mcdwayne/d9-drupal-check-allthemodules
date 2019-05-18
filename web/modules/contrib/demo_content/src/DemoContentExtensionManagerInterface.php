<?php

/**
 * @file
 * Contains \Drupal\demo_content\DemoContentExtensionManagerInterface.
 */

namespace Drupal\demo_content;
use Drupal\Core\Extension\Extension;

/**
 * Interface DemoContentExtensionManagerInterface
 *
 * @package Drupal\demo_content
 */
interface DemoContentExtensionManagerInterface {

  /**
   * Returns an extension by name.
   *
   * @param $name
   *  The name of the extension.
   * @return mixed
   *  An extension.
   */
  public function getExtension($name);

  /**
   * Returns an array of demo extensions.
   *
   * @return array
   *  An array of demo extensions.
   */
  public function getExtensions();

//  /**
//   * Determines whether a given extension is enabled.
//   *
//   * @param string $extension_name
//   *   The name of the extension.
//   *
//   * @return bool
//   *   TRUE if the extension is both installed and enabled.
//   */
//  public function extensionExists($extension_name);
//
//  /**
//   * Loads an extension by name.
//   *
//   * @param string $extension_name
//   *  The name of the extension.
//   * @return Extension
//   *  The extension instance.
//   */
//  public function loadByName($extension_name);
}
