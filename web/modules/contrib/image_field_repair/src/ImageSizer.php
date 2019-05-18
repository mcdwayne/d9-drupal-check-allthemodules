<?php

namespace Drupal\image_field_repair;

/**
 * Helper for get image size.
 */
class ImageSizer {

  /**
   * FastImage instance.
   *
   * @var \Drupal\image_field_repair\FastImage
   */
  private $fastImage;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $file_system;

  /**
   * @var array
   */
  private $scheme_is_local;

  /**
   * ImageSizer constructor.
   */
  public function __construct() {
    $this->fastImage = new FastImage();
    $this->file_system = \Drupal::service('file_system');
    $this->stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    // We initialize $scheme_is_local for some built-in PHP and Drupal core
    // defined stream wrappers. Others will be added when used.
    // Ignore warnings for non registered wrappers, if they are used in image
    // uris, we will warn over there.
    @$this->scheme_is_local = [
      'public' => stream_is_local('public://'),
      'private' => stream_is_local('private://'),
      'temporary' => stream_is_local('temporary://'),
      // Though file:// may point to a mounted network drive we initialize it as
      // true. Note:
      // - stream_is_local('file://') returns true.
      // -  stream_is_local('file://test.txt') returns false.
      'file' => stream_is_local('file://'),
      'data' => stream_is_local('data://'),
      'http' => stream_is_local('http://'),
      'https' => stream_is_local('https://'),
    ];
  }

  /**
   * Returns the dimensions of an image.
   *
   * @param string $uri
   *   Uri of image.
   *
   * @return array|bool
   *   Array with info about image dimensions: [0] => width, [1] => height.
   *   FALSE if image not found or unsupported format.
   */
  public function getDimensions($uri) {
    // Protocol relative uri's might be treated incorrectly.
    if ($uri[0] === '/' && $uri[1] === '/') {
      $uri = 'http:' . $uri;
    }

    // FastImage is faster for:
    // - Remote images (way faster!).
    // - (Very) large local images (but only marginally).
    // getimagesize() is faster for
    // - Small to medium sized local images (up to 2 or 3 mb file size). This is
    //   due to the PHP rule "PHP being an interpreted language, you can't beat
    //   the performance of internal functions".
    //
    // Therefore we first check if a uri is local and if so, use getimagesize().
    // Only then we use FastImage and, if necessary, getimagesize() again as
    // fallback when FastImage fails.
    $size = FALSE;
    if ($this->isUriLocal($uri)) {
      $size = @getimagesize($uri);
    }
    if (!$size) {
      @$this->fastImage->load($uri);
      $size = @$this->fastImage->getSize();
      if (!$size) {
        $size = @getimagesize($uri);
      }
    }
    return $size;
  }

  /**
   * Returns whether a uri refers to a local uri.
   *
   * @param string $uri
   *
   * @return bool
   *   True if $uri denotes a local file, false otherwise.
   */
  private function isUriLocal($uri) {
    $scheme = $this->file_system->uriScheme($uri);
    if ($scheme) {
      if (!isset($this->scheme_is_local[$scheme])) {
        $this->scheme_is_local[$scheme] = @stream_is_local($uri);
      }
      $result =  $this->scheme_is_local[$scheme];
    }
    else {
      // Plain old (local) file path.
      $result = TRUE;
    }
    return $result;
  }

}
