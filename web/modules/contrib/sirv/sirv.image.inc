<?php

/**
 * @file
 * Implements theme preprocessing for Sirv image templates.
 */

use Drupal\Component\Utility\Unicode;

/**
 * Prepares variables for Sirv image templates.
 *
 * Default template: sirv-image.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - width: The width of the image.
 *   - height: The height of the image.
 *   - profile_name: The name of the Sirv image profile to be applied.
 *   - attributes: Additional attributes to apply to the image.
 *   - uri: URI of the source image before styling.
 *   - alt: The alternative text for text-based browsers. HTML 4 and XHTML 1.0
 *     always require an alt attribute. The HTML 5 draft allows the alt
 *     attribute to be omitted in some cases. Therefore, this variable defaults
 *     to an empty string, but can be set to NULL for the attribute to be
 *     omitted. Usually, neither omission nor an empty string satisfies
 *     accessibility requirements, so it is strongly encouraged for code using
 *     '#theme' => 'image_style' to pass a meaningful value for this variable.
 *     - http://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
 *     - http://www.w3.org/TR/xhtml1/dtds.html
 *     - http://dev.w3.org/html5/spec/Overview.html#alt
 *   - title: The title text is displayed when the image is hovered in some
 *     popular browsers.
 *   - attributes: Associative array of attributes to be placed in the img tag.
 */
function template_preprocess_sirv_image(&$variables) {
  // Determine the dimensions of the styled image.
  $dimensions = [
    'width' => $variables['width'],
    'height' => $variables['height'],
  ];

  $variables['image'] = [
    '#theme' => 'image',
    '#width' => $dimensions['width'],
    '#height' => $dimensions['height'],
    '#attributes' => $variables['attributes'],
    '#style_name' => $variables['style_name'],
  ];

  $variables['image']['#uri'] = $variables['uri'];
  // Don't render the image by default, but allow other preprocess functions
  // to override that if they need to.
//  $variables['image']['#access'] = FALSE;

  if (isset($variables['alt']) || array_key_exists('alt', $variables)) {
    $variables['image']['#alt'] = $variables['alt'];
  }
  if (isset($variables['title']) || array_key_exists('title', $variables)) {
    $variables['image']['#title'] = $variables['title'];
  }
}

/**
 * Prepares variables for Sirv image formatter templates.
 *
 * Default template: sirv-image-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - sirv_profile: An optional Sirv profile.
 *   - url: An optional \Drupal\Core\Url object.
 */
function template_preprocess_sirv_image_formatter(&$variables) {
  if ($variables['sirv_profile']) {
    $variables['image'] = [
      '#theme' => 'sirv_image',
      '#sirv_profile' => $variables['sirv_profile'],
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
}
