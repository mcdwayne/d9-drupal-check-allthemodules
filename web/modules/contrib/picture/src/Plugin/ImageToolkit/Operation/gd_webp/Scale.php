<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp\Scale.
 */

namespace Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\Scale as GDScale;

/**
 * Defines GD2 Scale operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_webp_scale",
 *   toolkit = "gd_webp",
 *   operation = "scale",
 *   label = @Translation("Scale"),
 *   description = @Translation("Scales an image while maintaining aspect ratio. The resulting image can be smaller for one or both target dimensions.")
 * )
 */
class Scale extends GDScale {
}
