<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp\Rotate.
 */

namespace Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\Rotate as GDRotate;

/**
 * Defines GD2 rotate operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_webp_rotate",
 *   toolkit = "gd_webp",
 *   operation = "rotate",
 *   label = @Translation("Rotate"),
 *   description = @Translation("Rotates an image by the given number of degrees.")
 * )
 */
class Rotate extends GDRotate {
}
