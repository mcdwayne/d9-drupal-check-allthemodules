<?php

/**
 * @file
 * Implement a responsive image field, based on the file module's file field.
 */

use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Component\Utility\Unicode;

/**
 * Prepares variables for responsive image link to image style formatter templates.
 *
 * Default template: responsive-image-formatter-link-to-image-style-formatter.html.twig.
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
function template_preprocess_responsive_image_formatter_link_to_image_style_formatter(array &$variables) {
  $responsive_image_style = ResponsiveImageStyle::load($variables['responsive_image_style_id']);

  if ($responsive_image_style) {
    $variables['responsive_image'] = [
      '#theme' => 'responsive_image',
      '#responsive_image_style_id' => $variables['responsive_image_style_id'],
    ];
  }
  else {
    $variables['responsive_image'] = [
      '#theme' => 'image',
    ];
  }

  $item = $variables['item'];
  $attributes = [];

  // Do not output an empty 'title' attribute.
  if (Unicode::strlen($item->title) != 0) {
    $attributes['title'] = $item->title;
  }

  $attributes['alt'] = $item->alt;

  // Need to check that item_attributes has a value since it can be NULL.
  if ($variables['item_attributes']) {
    $attributes += $variables['item_attributes'];
  }

  if (($entity = $item->entity) && empty($item->uri)) {
    $variables['responsive_image']['#uri'] = $entity->getFileUri();
  }
  else {
    $variables['responsive_image']['#uri'] = $item->uri;
  }

  foreach (['width', 'height'] as $key) {
    $variables['responsive_image']["#$key"] = $item->$key;
  }

  $variables['responsive_image']['#attributes'] = $attributes;

  if (!empty($variables['url_attributes']['rel'])) {
    $variables['url_rel'] = implode(' ', $variables['url_attributes']['rel']);
  }

  if (!empty($variables['url_attributes']['class'])) {
    $variables['url_class'] = implode(' ', $variables['url_attributes']['class']);
  }
}
