<?php
/**
 * @file
 * Document.
 */

namespace Drupal\forena\FrxPlugin\Document;


interface DocumentInterface {

  /**
   * Clear the buffer
   */
  public function clear();

  /**
   * Header
   * @return mixed
   */
  public function header();

  /**
   * Write
   * @param $buffer
   * @return mixed
   */
  public function write($buffer);

  /**
   * @return mixed
   */
  public function footer();

  /**
   * Write the output to disk.
   * @return mixed
   */
  public function flush();

  /**
   * Set skin for the document.
   */
  public function setSkin($skin_name);

  /**
   * @param string $base_name
   *   file name
   */
  public function setFilename($filename);
}