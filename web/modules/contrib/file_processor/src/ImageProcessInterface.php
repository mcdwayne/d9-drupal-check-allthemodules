<?php

namespace Drupal\file_processor;
use Drupal\file\Entity\File;
use Drupal\Core\Config\Config;

interface ImageProcessInterface {

  /**
   * Method to process Image.
   */
  public function process(File $file, Config $config);

  /**
   * Binary default path
   *
   * @param ImmutableConfig $config
   *   Config that contain form configurations.
   *
   * @return string
   *   Path of binary.
   */
  public function getBinaryPath(Config $config);

}