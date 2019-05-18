<?php

namespace Drupal\migrate_json_source;

use GlobIterator;

class JsonGlobIterator extends GlobIterator {

  public function __construct($path) {
    $path = rtrim($path, '/');
    $path = $path . '/*.json';
    parent::__construct($path);
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    /** @var \SplFileInfo $current */
    $current = parent::current();
    $data = json_decode(file_get_contents($current->getPathname()), TRUE);
    if (!is_array($data)) {
      $data = [];
    }
    $data['id'] = $current->getBasename();
    return $data;
  }

}
