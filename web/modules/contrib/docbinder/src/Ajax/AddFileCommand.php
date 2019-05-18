<?php

namespace Drupal\docbinder\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\file\Entity\File;


class AddFileCommand implements CommandInterface {

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
    if ($status == 200) {
      $fid = $this->tempStore->get('addedLast');
      /** @var File $file */
      $file = $files[$fid];
      return [
        'command' => 'addFile',
        'fid' => $fid,
        'filename' => $file->getFilename(),
        'status' => $status
      ];
    }
    elseif ($status == 304) {
      $fid = $this->tempStore->get('addedLast');
      /** @var File $file */
      $file = $files[$fid];
      return [
        'command' => 'addFile',
        'fid' => $fid,
        'filename' => $file->getFilename(),
        'status' => $status
      ];
    }
    else {
      return [
        'command' => 'addFile',
        'fid' => 0,
        'filename' => '',
        'status' => 500
      ];
    }
  }
}
