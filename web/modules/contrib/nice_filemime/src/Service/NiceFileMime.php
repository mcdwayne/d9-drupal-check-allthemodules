<?php

namespace Drupal\nice_filemime\Service;

use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\Yaml\Yaml;

/**
 * Service class for NiceFileMime.
 */
class NiceFileMime {

  /**
   * Create the NiceFileMime client.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->config = $configFactory->get('nice_filemime.settings');
  }

  public function getNiceFileMime($filemime) {
    // All our nice mimes.
    $niceFileMimes = $this->mapNiceFileMime();

    // Return a nice filemime is one exists.
    if (array_key_exists($filemime, $niceFileMimes)) {
      return $niceFileMimes[$filemime];
    }

    // No result do a pass through.
    return $filemime;
  }

  private function mapNiceFileMime() {
    $niceFileMimesTemp = $this->config->get('nice_filemimes');

    // TODO Find a nicer way to str_replace all keys containing _@_ to .
    // example application/vnd_@_hzn-3d-crossword to application/vnd.hzn-3d-crossword
    // See https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Config%21ConfigBase.php/function/ConfigBase%3A%3AvalidateKeys/8.2.x
    $niceFileMimesReplaced = str_replace('_@_', '.', Yaml::dump($niceFileMimesTemp));
    $niceFileMimes = Yaml::parse($niceFileMimesReplaced);

    return $niceFileMimes;
  }
  
}