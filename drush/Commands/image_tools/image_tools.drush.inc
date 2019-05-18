<?php

/**
 * @file
 * Drush 8 command.
 */

use Drupal\image_tools\Services\ImageService;

/**
 * Implements hook_drush_command().
 */
function image_tools_drush_command() {
  $commands = [];

  $commands['convert-png-to-jpg'] = [
    'description' => dt('Convert all Images with type png to jpg'),
    'aliases' => ['cptj'],
    'core' => ['8+'],
    'options' => [
      'dry_run' => 'Display images which should be converted. No image will be modified.',
    ],
  ];

  $commands['resize-images'] = [
    'description' => dt('Resize JPG Images to an width of ' . ImageService::IMAGE_TOOLS_DEFAULT_MAX_WIDTH . ' pixel.'),
    'aliases' => ['ri'],
    'core' => ['8+'],
    'options' => [
      'dry_run' => 'Display images which should be converted. No image will be modified.',
      'max_width' => 'The max width for the images. Images larger then max_width getting  resized. Default ' . ImageService::IMAGE_TOOLS_DEFAULT_MAX_WIDTH,
      'include_png' => 'Also includes PNG images. With transparency the background will be white.',
    ],
  ];

  return $commands;
}

/**
 * Convert PNG to JPG.
 */
function drush_image_tools_convert_png_to_jpg() {
  $dry_run = drush_get_option('dry_run', FALSE);

  /** @var \Drupal\image_tools\Services\ImageService $imageService */
  $imageService = \Drupal::service('image_tools.conversion.service');

  $files = $imageService->loadPngImages();

  if ($dry_run) {
    foreach ($files as $fid => $element) {
      drush_print($fid . " | " . basename($element['path']) . ($element['transparency'] ? " | has transparency (alpha channel)." : ""));
    }

    return;
  }

  list($images_converted, $current_size, $new_size, $saved_size) = $imageService->convertPngImagesToJpeg($files);

  drush_print("Converted $images_converted images from png to jpg. We had $current_size MB and reduced it to $new_size MB. We saved $saved_size MB.");
}

/**
 * Resize Images.
 */
function drush_image_tools_resize_images() {
  $dry_run = drush_get_option('dry_run');
  $max_width = drush_get_option('max_width', ImageService::IMAGE_TOOLS_DEFAULT_MAX_WIDTH);
  $include_png = drush_get_option('include_png', FALSE);

  /** @var \Drupal\image_tools\Services\ImageService $imageService */
  $imageService = \Drupal::service('image_tools.conversion.service');
  $files = $imageService->findLargeWidthImages($max_width, $include_png);

  if ($dry_run) {
    foreach ($files as $fid => $element) {
      drush_print($fid . " | " . basename($element['path']) . (isset($element['transparency']) && $element['transparency'] ? " | has transparency (alpha channel)." : ""));
    }

    return;
  }

  list($images_converted, $current_size, $new_size) = $imageService->resizeImages($files, $max_width);

  drush_print("Resized $images_converted images to the an max width of " . $max_width . " pixels. We had $current_size MB, now we need $new_size MB.");
}
