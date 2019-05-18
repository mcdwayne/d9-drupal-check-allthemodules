<?php

/**
 * @file
 * Contains \Drupal\image_replace\Plugin\ImageToolkit\Operation\gd\Replace.
 */

namespace Drupal\image_replace\Plugin\ImageToolkit\Operation\gd;

use Drupal\Core\Image\ImageInterface;
use Drupal\system\Plugin\ImageToolkit\GDToolkit;
use Drupal\system\Plugin\ImageToolkit\Operation\gd\GDImageToolkitOperationBase;

/**
 * Defines GD2 image_replace operation.
 *
 * @ImageToolkitOperation(
 *   id = "image_replace_gd",
 *   toolkit = "gd",
 *   operation = "image_replace",
 *   label = @Translation("Replace"),
 *   description = @Translation("Swap the original image with a replacement image."),
 * )
 */
class Replace extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return array(
      'replacement_image' => array(
        'description' => 'The replacement image',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    if (!($arguments['replacement_image'] instanceof ImageInterface || !($arguments['replacement_image']->getToolkit() instanceof GDToolkit))) {
      throw new \InvalidArgumentException("Invalid replacement image specified for the 'image_replace' operation.");
    }
    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = array()) {
    // Create a new resource of the required dimensions, and replace the
    // original resource on it with resampling. Destroy the original resource
    // upon success.
    $replacement_toolkit = $this->getReplacementImageToolkit($arguments);

    $original_resource = $this->getToolkit()->getResource();
    $data = array(
      'width' => $replacement_toolkit->getWidth(),
      'height' => $replacement_toolkit->getHeight(),
      'extension' => image_type_to_extension($this->getToolkit()->getType(), FALSE),
      'transparent_color' => $replacement_toolkit->getTransparentColor(),
      'is_temp' => TRUE,
    );
    if ($this->getToolkit()->apply('create_new', $data)) {
      if (imagecopy($this->getToolkit()->getResource(), $replacement_toolkit->getResource(), 0, 0, 0, 0, $data['width'], $data['height'])) {
        imagedestroy($original_resource);
        return TRUE;
      }
      else {
        // In case of failure, destroy the temporary resource and restore
        // the original one.
        imagedestroy($this->getToolkit()->getResource());
        $this->getToolkit()->setResource($original_resource);
      }
    }

    return FALSE;
  }

  /**
   * Returns the correctly typed replacement image toolkit for GD operations.
   *
   * @param array $arguments
   *   An associative array of data to be used by the toolkit operation.
   *
   * @return \Drupal\system\Plugin\ImageToolkit\GDToolkit
   *   The correctly typed replacement image toolkit for GD operations.
   */
  protected function getReplacementImageToolkit(array $arguments = array()) {
    return $arguments['replacement_image']->getToolkit();
  }

}
