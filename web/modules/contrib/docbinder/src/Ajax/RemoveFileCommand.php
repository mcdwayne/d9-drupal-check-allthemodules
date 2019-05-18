<?php

namespace Drupal\docbinder\Ajax;

use Drupal\Core\Ajax\CommandInterface;


class RemoveFileCommand implements CommandInterface {

  /**
   * The tempstore.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  // Pass the dependency to the object constructor
  public function __construct($tempStore) {
    // For "mymodule_name," any unique namespace will do
    $this->tempStore = $tempStore;
  }

  public function render() {
    $status = $this->tempStore->get('statusCode');
    if ($status == 200) {
      $fid = $this->tempStore->get('removedLast');
      return [
        'command' => 'removeFile',
        'fid' => $fid,
        'status' => $status
      ];
    }
    elseif ($status == 404) {
      $fid = $this->tempStore->get('removedLast');
      return [
        'command' => 'removeFile',
        'fid' => $fid,
        'status' => $status
      ];
    }
    else {
      return [
        'command' => 'removeFile',
        'fid' => 0,
        'filename' => '',
        'status' => 500
      ];
    }
  }
}
