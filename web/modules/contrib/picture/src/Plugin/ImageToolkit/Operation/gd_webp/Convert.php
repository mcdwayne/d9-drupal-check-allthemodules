<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp\Convert.
 */

namespace Drupal\picture\Plugin\ImageToolkit\Operation\gd_webp;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\Convert as GDConvert;

/**
 * Defines GD2 convert operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_webp_convert",
 *   toolkit = "gd_webp",
 *   operation = "convert",
 *   label = @Translation("Convert"),
 *   description = @Translation("Instructs the toolkit to save the image with a specified extension.")
 * )
 */
class Convert extends GDConvert {
}
