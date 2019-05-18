<?php

namespace Drupal\rokka\RokkaAdapter;

/**
 *
 */
interface RokkaStackInterface {

  /**
   * @return array The Stack options.
   */
  public function getStackOptions();

  /**
   * @return array The Drupal Image Style name.
   */
  public function getImageStyle();

  /**
   * @return string The STack creation time.
   */
  public function getCreatedTime();

  /**
   * @param $value
   */
  public function setJpgQuality($value);

  /**
   * @return int
   */
  public function getJpgQuality();

  /**
   * @param $value
   */
  public function setPngCompressionLevel($value);

  /**
   * @return int
   */
  public function getPngCompressionLevel();

  /**
   * @param $value
   */
  public function setInterlacingMode($value);

  /**
   * @return string
   */
  public function getInterlacingMode();

}
