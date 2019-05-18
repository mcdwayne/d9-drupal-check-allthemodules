<?php

namespace Drupal\image_catcher;

/**
 * ImageCatcherInterface.
 */
interface ImageCatcherInterface {

  /**
   * Create an image file from a base64 content.
   *
   * @param string $image_base64
   *   The image base64 content.
   * @param string $dir_name
   *   The directory name in which we want to save the image.
   * @param string $image_name
   *   The name the image will have after being created (without the extension).
   *
   * @return int|bool
   *   File id if the file has successfully been created, else FALSE.
   */
  public function createFromBase64(string $image_base64, string $dir_name, string $image_name);

  /**
   * Create an image file from an external url.
   *
   * @param string $image_url
   *   The image url.
   * @param string $dir_name
   *   The directory name in which we want to save the image.
   *
   * @return int|bool
   *   File id if the file has successfully been created, else FALSE.
   */
  public function createFromUrl(string $image_url, string $dir_name);

}
