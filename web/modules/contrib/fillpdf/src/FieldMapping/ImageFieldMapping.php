<?php

namespace Drupal\fillpdf\FieldMapping;

use Drupal\fillpdf\FieldMapping;

/**
 * Represents a mapping between a PDF image field and a merge value.
 *
 * ImageFieldMapping objects are immutable; replace the value by calling the
 * constructor again if the value needs to change.
 */
class ImageFieldMapping extends FieldMapping {

  /**
   * The image file's extension.
   *
   * May be 'jpg', 'png', or 'gif'.
   *
   * @var string
   */
  protected $extension;

  /**
   * Constructs an ImageFieldMapping object.
   *
   * @param string $data
   *   String containing the image data, as returned by file_get_contents() and
   *   not encoded.
   * @param string $extension
   *   (optional) The original extension corresponding to the image data. If the
   *   backend doesn't need to know the extension and you don't want extensions
   *   to be checked, you can leave it blank.
   *
   * @throws \InvalidArgumentException
   *   If the extension isn't one of 'jpg', 'png', or 'gif'.
   */
  public function __construct($data, $extension = NULL) {
    parent::__construct($data);

    if (isset($extension) && !in_array($extension, ['jpg', 'png', 'gif'])) {
      throw new \InvalidArgumentException('Extension must be one of: jpg, png, gif.');
    }

    $this->extension = $extension;
  }

  /**
   * Gets the image file's extension.
   *
   * @return string
   *   The file's extension.
   */
  public function getExtension() {
    return $this->extension;
  }

}
