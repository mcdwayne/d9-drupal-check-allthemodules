<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp\Crop.
 */

namespace Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\Crop as GDCrop;

/**
 * Defines GD2 Crop operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_webp_crop",
 *   toolkit = "gd_webp",
 *   operation = "crop",
 *   label = @Translation("Crop"),
 *   description = @Translation("Crops an image to a rectangle specified by the given dimensions.")
 * )
 */
class Crop extends GDCrop {
}
