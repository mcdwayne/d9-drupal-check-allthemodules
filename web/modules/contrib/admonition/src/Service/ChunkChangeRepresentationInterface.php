<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 8/15/16
 * Time: 10:02 AM
 */

namespace Drupal\admonition\Service;


interface ChunkChangeRepresentationInterface {

  /**
   * Translate tags in content from storage rep to display rep.
   * @param string $content_in Content to translate.
   * @return string Translated content.
   */
  public function storageToDisplay($content_in);

  /**
   * Translate tags in content from display rep to storage rep.
   * @param string $content_in Content to translate.
   * @return string Translated content.
   */

  public function displayToStorage($content_in);

}