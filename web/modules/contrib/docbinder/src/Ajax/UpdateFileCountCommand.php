<?php

namespace Drupal\docbinder\Ajax;

use Drupal\Core\Ajax\CommandInterface;


class UpdateFileCountCommand implements CommandInterface {

  /**
   * The tempstore.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  // Pass the dependency to the object constructor
  public function __construct($tempStore) {
    $this->tempStore = $tempStore;
  }

  public function render() {
    $status = $this->tempStore->get('statusCode');
    $files = $this->tempStore->get('files');
    $count = count($files);
    return [
      'command' => 'updateFileCount',
      'count' => $count,
      'status' => $status
    ];
  }
}
