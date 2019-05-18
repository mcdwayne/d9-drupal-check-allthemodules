<?php

namespace Drupal\file_processor\Plugin\file_processor\Image;

use Drupal\file_processor\ImageProcessInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\file\Entity\File;
use Drupal\Core\Config\Config;

/**
 * Jfifremove file.
 *
 * @Plugin(
 *   id = "file_processor_jfifremove",
 *   name = "jfifremove",
 *   mime_type = "image/jpeg"
 * )
 */
class jfifremoveImageMin extends PluginBase implements ImageProcessInterface {

  /**
   * Method to process Image.
   */
  public function process(File $file, Config $config) {
    $uri = $file->getFileUri();
    $url = drupal_realpath($uri);

    if (file_exists($url)) {
      $cmd = $this->getBinaryPath($config);

      ob_start();
      passthru("{$cmd} < " . escapeshellarg($url));
      $output = ob_get_contents();
      ob_end_clean();

      if (!empty($output)) {
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