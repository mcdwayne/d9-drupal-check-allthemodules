<?php
/**
 * @file
 * Contains e-Boks sender definition.
 */

namespace Drupal\eboks;

/**
 * Class e-Boks sender.
 *
 * @package Drupal\eboks
 */
interface EboksSenderInterface {

  /**
   * Initialization of sending process.
   */
  public function init();

  /**
   * Send e-Boks message callback.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function send();

  /**
   * Validation get function.
   */
  public function isValid();

}
