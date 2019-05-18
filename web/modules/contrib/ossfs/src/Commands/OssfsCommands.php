<?php

namespace Drupal\ossfs\Commands;

use Drupal\ossfs\OssfsTransfer;
use Drush\Commands\DrushCommands;

class OssfsCommands extends DrushCommands {

  /**
   * The ossfs transfer.
   *
   * @var OssfsTransfer
   */
  protected $transfer;

  /**
   * Constructs a new OssfsCommands.
   *
   * @param \Drupal\ossfs\OssfsTransfer $transfer
   *   The transfer.
   */
  public function __construct(OssfsTransfer $transfer) {
    $this->transfer = $transfer;
  }

  /**
   * Uploads all the local public files into OSS.
   *
   * @command ossfs:upload-public
   * @param string $sub_dir The sub directory to upload.
   * @option recursive Upload files recursively.
   * @aliases ossfs-up
   * @usage ''
   *   Upload files in "public://" directory not including sub directories.
   * @usage image
   *   Upload files in "public://image" directory not including sub directories.
   * @usage image --recursive
   *   Upload all the files in "public://image" directory including sub directories.
   *
   * @throws \Exception
   */
  public function uploadPublic($sub_dir = '', $options = ['recursive' => FALSE]) {
    $this->output()->writeln('You should have read "UPLOAD LOCAL PUBLIC FILES TO OSS" section in README.md.');
    $this->output()->writeln('This command only is useful if you are going to serve public files in OSS.');

    if (!$this->io()->confirm('Are you sure?')) {
      return;
    }

    if (($message = $this->transfer->validateConfig()) !== TRUE) {
      throw new \Exception($message);
    }

    $this->output()->writeln('Uploading files ...');
    foreach ($this->transfer->uploadPublic(trim($sub_dir, '\/'), $options['recursive']) as $msg) {
      $this->output()->writeln($msg);
    }
  }

  /**
   * Syncs OSS object metadata to the local storage.
   *
   * @command ossfs:sync-metadata
   * @aliases ossfs-sm
   *
   * @throws \Exception
   */
  function syncMetadata() {
    if (($message = $this->transfer->validateConfig()) !== TRUE) {
      throw new \Exception($message);
    }

    $this->output()->writeln('Syncing OSS metadata ...');
    $count = $this->transfer->syncMetadata();
    $this->output()->writeln($count . ' records were synced.');
  }

}
