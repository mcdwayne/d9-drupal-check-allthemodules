<?php

namespace Drupal\acquia_contenthub\Plugin\FileSchemeHandler;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\file\FileInterface;

/**
 * Interface FileSchemeHandlerInterface.
 *
 * @package Drupal\acquia_contenthub\Plugin\FileSchemeHandler
 */
interface FileSchemeHandlerInterface extends PluginInspectionInterface {

  /**
   * Add attributes to the CDF to support import of a file by this scheme.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $object
   *   The CDF Object.
   * @param \Drupal\file\FileInterface $file
   *   The file to add to the CDF Object.
   */
  public function addAttributes(CDFObject $object, FileInterface $file);

  /**
   * Makes file available to Drupal through the correct stream wrapper.
   *
   * This does not return the file, but will save it with the appropriate
   * stream wrapper for Drupal to utilize.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $object
   *   The CDFObject from which to extract details about getting the file.
   *
   * @return bool
   *   Whether the file successfully saved or not.
   */
  public function getFile(CDFObject $object);

}
