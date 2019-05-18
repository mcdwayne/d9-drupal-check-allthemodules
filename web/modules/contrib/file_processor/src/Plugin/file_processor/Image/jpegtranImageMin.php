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
 *   id = "file_processor_jpegtran",
 *   name = "JPEGTRAN",
 *   mime_type = "image/jpeg"
 * )
 */
class jpegtranImageMin extends PluginBase implements ImageProcessInterface {

  /**
   * Method to process Image.
   */
  public function process(File $file, Config $config) {
    $uri = $file->getFileUri();
    $url = drupal_realpath($uri);

    if (file_exists($url)) {
      $cmd = $this->getBinaryPath($config);

      $cmd = "{$cmd} -copy none -optimize " . escapeshellarg($url);
      ob_start();
      passthru($cmd);
      $output = ob_get_contents();
      ob_end_clean();

      if (!empty($output)) {
        // Replace image to compressed version.
        file_unmanaged_save_data($output, $url, FILE_EXISTS_REPLACE);
      }
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