<?php

namespace Drupal\file_processor\Plugin\file_processor\Image;

use Drupal\file_processor\ImageProcessInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\file\Entity\File;
use Drupal\Core\Config\Config;

/**
 * JpgImageMin file.
 *
 * @Plugin(
 *   id = "file_processor_optipng",
 *   name = "OPTIPNG",
 *   mime_type = "image/png"
 * )
 */
class optipngImageMin extends PluginBase implements ImageProcessInterface {
  /**
   * Method to process Image.
   */
  public function process(File $file, Config $config) {
    $uri = $file->getFileUri();
    $url = drupal_realpath($uri);

    if (file_exists($url)) {
      $cmd = $this->getBinaryPath($config);

      $cmd = "{$cmd} -quiet -o7 " . escapeshellarg($url);
      exec($cmd);
    }

    $file->set('process', TRUE);
    $file->save();
  }

  /**
   * @inheritdoc
   */
  public function getBinaryPath(Config $config) {
    if (!empty($config->get($this->configuration['id']))) {
      return $config->get($this->configuration['id']);
    }

    return;
  }
} 