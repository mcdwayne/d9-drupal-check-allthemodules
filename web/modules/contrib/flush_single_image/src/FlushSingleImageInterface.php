<?php

namespace Drupal\flush_single_image;

/**
 * Interface FlushSingleImageInterface.
 */
interface FlushSingleImageInterface {

  /**
   * Flush a single image from all styles that may have a version of it.
   *
   * @param string $path
   *   The filename to flush from all styles. This can be a relative path which
   *   will be givent the default stream wrapper scheme or you can include the
   *   full URI (with stream wrapper).
   *   Examples: public://path/to/file.jpg, private://path/to/file.jpg etc...
   */
  public function flush($path);

  /**
   * The the image styles currently cached for given image path.
   *
   * @param string $path
   *   The filename to flush from all styles. This can be a relative path which
   *   will be givent the default stream wrapper scheme or you can include the
   *   full URI (with stream wrapper).
   *   Examples: public://path/to/file.jpg, private://path/to/file.jpg etc...
   */
  public function getStylePaths($path);

}
