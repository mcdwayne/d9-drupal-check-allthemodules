<?php

/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

use Drupal\Component\Utility\Unicode;

/**
 * Prepares variables for image link to image style formatter templates.
 *
 * Default template: image-formatter-link-to-image-style-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - url: A \Drupal\Core\Url object.
 *   - url_attributes: An optional associative array of html attributes to be
 *     placed in the a tag.
 *   - image_style: An optional image style.
 */
function template_preprocess_image_formatter_link_to_image_style_formatter(array &$variables) {
  if ($variables['image_style']) {
    $variables['image'] = [
      '#theme' => 'image_style',
      '#style_name' => $variables['image_style'],
    ];
  }
  else {
    $variables['image'] = [
      '#theme' => 'image',
    ];
  }
  $variables['image']['#attributes'] = $variables['item_attributes'];

  $item = $variables['item'];

  // Do not output an empty 'title' attribute.
  if (Unicode::strlen($item->title) != 0) {
    $variables['image']['#title'] = $item->title;
  }

  if (($entity = $item->entity) && empty($item->uri)) {
    $variables['image']['#uri'] = $entity->getFileUri();
  }
  else {
    $variables['image']['#uri'] = $item->uri;
  }

  foreach (['width', 'height', 'alt'] as $key) {
    $variables['image']["#$key"] = $item->$key;
  }

  if (!empty($variables['url_attributes']['rel'])) {
    $variables['url_rel'] = implode(' ', $variables['url_attributes']['rel']);
  }
  if (!empty($variables['url_attributes']['class'])) {
    $variables['url_class'] = implode(' ', $variables['url_attributes']['class']);
  }
}
