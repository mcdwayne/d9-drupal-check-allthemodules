<?php

namespace Drupal\cloudsight;

/**
 * Interface CloudsightApiServiceInterface.
 */
interface CloudsightApiServiceInterface {

  /**
   * Send an image to the Cloudsight API for processing.
   * https://cloudsight.docs.apiary.io/#reference/0/images-collection
   *
   * @File $file
   *
   * @return mixed
   */
  public function sendImage($file);

}
