<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp\Desaturate.
 */

namespace Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\Desaturate as GDDesaturate;

/**
 * Defines GD2 Desaturate operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_webp_desaturate",
 *   toolkit = "gd_webp",
 *   operation = "desaturate",
 *   label = @Translation("Desaturate"),
 *   description = @Translation("Converts an image to grayscale.")
 * )
 */
class Desaturate extends GDDesaturate {
}
