<?php
namespace Drupal\sir_trevor;

interface IconSvgMergerInterface {
  /**
   * @param string[] $fileNames
   * @return string
   */
  public function merge(array $fileNames);
}