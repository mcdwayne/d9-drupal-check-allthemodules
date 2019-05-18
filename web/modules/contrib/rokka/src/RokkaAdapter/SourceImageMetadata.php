<?php

namespace Drupal\rokka\RokkaAdapter;

/**
 *
 */
interface SourceImageMetadata {

  /**
   * @return string
   */
  public function getHash();

  /**
   * @return string
   */
  public function getCreatedTime();

  /**
   * @return string
   */
  public function getFilesize();

  /**
   * @return string
   */
  public function getUri();

}
