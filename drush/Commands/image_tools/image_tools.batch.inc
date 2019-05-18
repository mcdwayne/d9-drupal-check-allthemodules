<?php

/**
 * @file
 * Batch handling for conversion and resizing.
 */

/**
 * Convert PNG Images to JPG.
 *
 * @param array $images
 *   Images to convert.
 * @param mixed $context
 *   Context.
 */
function convert_pngs_to_jpg(array $images, &$context) {
  /** @var \Drupal\image_tools\Services\ImageService $imageService */
  $imageService = \Drupal::service('image_tools.conversion.service');

  list($images_converted, $old_size, $new_size) = $imageService->convertPngImagesToJpeg($images);

  $context['results']['images_converted'] = isset($context['results']['images_converted']) ? $context['results']['images_converted'] + $images_converted : $images_converted;
  $context['results']['old_size'] = isset($context['results']['old_size']) ? $context['results']['old_size'] + $old_size : $old_size;
  $context['results']['new_size'] = isset($context['results']['new_size']) ? $context['results']['new_size'] + $new_size : $new_size;
  $context['results']['saved_size'] = isset($context['results']['saved_size']) ? $context['results']['saved_size'] + ($old_size - $new_size) : ($old_size - $new_size);
}

/**
 * Resize JPGS.
 *
 * @param array $images
 *   Images to resize.
 * @param int $max_width
 *   Max width of the images.
 * @param mixed $context
 *   Context.
 */
function resize_jpgs(array $images, $max_width, &$context) {
  /** @var \Drupal\image_tools\Services\ImageService $imageService */
  $imageService = \Drupal::service('image_tools.conversion.service');

  list($images_converted, $old_size, $new_size) = $imageService->resizeImages($images, $max_width);

  $context['results']['images_converted'] = isset($context['results']['images_converted']) ? $context['results']['images_converted'] + $images_converted : $images_converted;
  $context['results']['old_size'] = isset($context['results']['old_size']) ? $context['results']['old_size'] + $old_size : $old_size;
  $context['results']['new_size'] = isset($context['results']['new_size']) ? $context['results']['new_size'] + $new_size : $new_size;
}

/**
 * Handler for PNG conversion finished.
 *
 * @param bool $success
 *   Success.
 * @param array $results
 *   Results.
 * @param array $operations
 *   Operations.
 */
function png_conversion_finished($success, array $results, array $operations) {
  $messenger = \Drupal::messenger();
  $t = \Drupal::translation();

  if ($success) {
    $messenger->addMessage($t->translate(
        "Converted " . $results['images_converted'] . " images from png to jpg. We had " . $results['old_size'] . " MB and reduced it to " . $results['new_size'] . " MB. We saved " . $results['saved_size'] . " MB"
    ));
  }
  else {
    $error_operation = reset($operations);
    $messenger->addMessage($t->translate("Error on Batch operation: " . $error_operation[0]));
  }

}

/**
 * Handler for JPG resizing finished.
 *
 * @param bool $success
 *   Success.
 * @param array $results
 *   Results.
 * @param array $operations
 *   Operations.
 */
function jpg_resizing_finished($success, array $results, array $operations) {
  $messenger = \Drupal::messenger();
  $t = \Drupal::translation();

  if ($success) {
    $messenger->addMessage($t->translate(
        "Resized " . $results['images_converted'] . " images. We had " . $results['old_size'] . " MB, now we need  " . $results['new_size'] . " MB. "
    ));
  }
  else {
    $error_operation = reset($operations);
    $messenger->addMessage($t->translate("Error on Batch operation: " . $error_operation[0]));
  }

}
