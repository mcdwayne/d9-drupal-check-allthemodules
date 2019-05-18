<?php

namespace Drupal\service_description\Discovery;

use Drupal\Component\Discovery\DiscoverableInterface;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\FileSystem\RegexDirectoryIterator;
use Drupal\Component\Serialization\Json;

/**
 * Provides discovery for JSON files within a given set of directories.
 */
class JsonDirectoryDiscovery extends JsonDiscovery {

  /**
   * The subdirectory to look for in each directory.
   *
   * @var string
   */
  protected $subDirectory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $directories, $name = 'description', $sub_directory = 'service_description') {
    $this->subDirectory = $sub_directory;
    parent::__construct($name, $directories);
  }

  /**
   * Returns an array of file paths, keyed by provider.
   *
   * @return array
   */
  protected function findFiles() {
    $files = [];
    foreach ($this->directories as $provider => $directory) {
      $file = $directory . '/' . $this->subDirectory . '/' . $this->name . '.json';
      if (file_exists($file)) {
        $files[$provider] = $file;
      }
    }
    return $files;
  }

}
