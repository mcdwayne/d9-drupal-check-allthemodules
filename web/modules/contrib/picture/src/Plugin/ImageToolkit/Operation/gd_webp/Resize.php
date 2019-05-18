<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp\Resize.
 */

namespace Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\Resize as GDResize;

/**
 * Defines GD2 resize operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_webp_resize",
 *   toolkit = "gd_webp",
 *   operation = "resize",
 *   label = @Translation("Resize"),
 *   description = @Translation("Resizes an image to the given dimensions (ignoring aspect ratio).")
 * )
 */
class Resize extends GDResize {
}
