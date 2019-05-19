<?php

/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

/**
 * Prepares variables for RSS enclosure image formatter templates.
 *
 * Default template: rss-enclosure-image-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - image_style: An optional image style.
 */
function template_preprocess_rss_enclosure_image_formatter(array &$variables) {
  $item = $variables['item'];

  if (($entity = $item->entity) && empty($item->uri)) {
    $imageUri = $entity->getFileUri();
  }
  else {
    $imageUri = $item->uri;
  }

  // Create image derivative if it not already exists to get file size.
  $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($variables['image_style']);
  $derivativeUri = $style->buildUri($imageUri);
  if (!file_exists($derivativeUri)) {
    $style->createDerivative($imageUri, $derivativeUri);
  }

  // Prepare enclosure data.
  $fInfo = finfo_open(FILEINFO_MIME_TYPE);
  $enclosureUrl = $style->buildUrl($imageUri);
  $enclosureLength = filesize($derivativeUri);
  $enclosureType = finfo_file($fInfo, $derivativeUri);
  finfo_close($fInfo);

  // Fix missing protocol for image URL with enabled CDN module.
  if (!filter_var($enclosureUrl, FILTER_VALIDATE_URL)) {
    $enclosureUrl = 'https:' . $enclosureUrl;
  }

  $variables['url'] = $enclosureUrl;
  $variables['length'] = $enclosureLength;
  $variables['type'] = $enclosureType;
}

/**
 * Prepares variables for RSS enclosure reference formatter templates.
 *
 * Default template: rss-enclosure-reference-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - image_style: An optional image style.
 */
function template_preprocess_rss_enclosure_reference_formatter(array &$variables) {
  $item = $variables['item'];

  if (($entity = $item) && $entity->getType()->getPluginId() == 'image') {
    $source_field = \Drupal::entityTypeManager()->getStorage('media_bundle')
      ->load('image')
      ->getTypeConfiguration()['source_field'];
    if (!empty($source_field)) {
      $imageUri = $entity->{$source_field}->first()->entity->getFileUri();
    }
  }
  else {
    return;
  }
  // Create image derivative if it not already exists to get file size.
  $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($variables['image_style']);
  $derivativeUri = $style->buildUri($imageUri);
  if (!file_exists($derivativeUri)) {
    $style->createDerivative($imageUri, $derivativeUri);
  }

  // Prepare enclosure data.
  $fInfo = finfo_open(FILEINFO_MIME_TYPE);
  $enclosureUrl = $style->buildUrl($imageUri);
  $enclosureLength = filesize($derivativeUri);
  $enclosureType = finfo_file($fInfo, $derivativeUri);
  finfo_close($fInfo);

  // Fix missing protocol for image URL with enabled CDN module.
  if (!filter_var($enclosureUrl, FILTER_VALIDATE_URL)) {
    $enclosureUrl = 'https:' . $enclosureUrl;
  }

  $variables['url'] = $enclosureUrl;
  $variables['length'] = $enclosureLength;
  $variables['type'] = $enclosureType;
}
