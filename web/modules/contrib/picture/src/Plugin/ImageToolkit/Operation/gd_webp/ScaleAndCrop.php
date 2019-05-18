<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp\ScaleAndCrop.
 */

namespace Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\ScaleAndCrop as GDScaleAndCrop;

/**
 * Defines GD2 Scale and crop operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_webp_scale_and_crop",
 *   toolkit = "gd_webp",
 *   operation = "scale_and_crop",
 *   label = @Translation("Scale and crop"),
 *   description = @Translation("Scales an image to the exact width and height given. This plugin achieves the target aspect ratio by cropping the original image equally on both sides, or equally on the top and bottom. This function is useful to create uniform sized avatars from larger images.")
 * )
 */
class ScaleAndCrop extends GDScaleAndCrop {
}
