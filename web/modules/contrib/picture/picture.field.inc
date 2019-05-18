<?php

/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

/**
 * Prepares variables for image formatter templates.
 *
 * Default template: image-sizes.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - image_styles: An array of image styles.
 *   - fallback_image_style: The fallback image style.
 *   - sizes: The sizes attribute for viewport-based selection of images.
 *     - http://www.whatwg.org/specs/web-apps/current-work/multipage/embedded-content.html#introduction-3:viewport-based-selection-2
 *   - path: An optional array containing the link 'path' and link 'options'.
 */
function template_preprocess_image_sizes_formatter(&$variables) {
  $variables['source'] = array(
    '#theme' => 'responsive_image_source',
    '#sizes' => $variables['sizes'],
  );

  $item = $variables['item'];
  if (($entity = $item->entity) && empty($item->uri)) {
    $path = $entity->getFileUri();
  }
  else {
    $path = $item->uri;
  }

  $srcset = array();
  foreach ($variables['image_styles'] as $image_style_name) {
    // Get the dimensions.
    $dimensions = responsive_image_get_image_dimensions(
      $image_style_name,
      array(
        'width' => $item->width,
        'height' => $item->height,
      )
    );

    // Add the image source with its width descriptor.
    // http://www.w3.org/html/wg/drafts/html/master/embedded-content.html#image-candidate-string
    $srcset[$dimensions['width']] = array(
     'uri' => _responsive_image_image_style_url($image_style_name, $path),
     'width' => $dimensions['width'] . 'w',
    );
  }

  // Sort by dimension.
  ksort($srcset);

  $variables['source']['#srcset'] = $srcset;
  $variables['fallback_image'] = array(
    '#theme' => 'image',
  );
  $variables['fallback_image']['#attributes'] = $variables['item_attributes'];

  // Do not output an empty 'title' attribute.
  if (drupal_strlen($item->title) != 0) {
    $variables['fallback_image']['#title'] = $item->title;
  }

  if ($variables['fallback_image_style'] !== RESPONSIVE_IMAGE_EMPTY_IMAGE) {
    $variables['fallback_image']['#srcset'] = array(array('uri' => _responsive_image_image_style_url($variables['fallback_image_style'], $path)));
  }
  $variables['fallback_image']["#alt"] = $item->alt;

  // The link path and link options are both optional, but for the options to be
  // processed, the link path must at least be an empty string.
  // @todo Add support for route names.
  $variables['url'] = NULL;
  if (isset($variables['path']['path'])) {
    $path = $variables['path']['path'];
    $options = isset($variables['path']['options']) ? $variables['path']['options'] : array();
    $variables['url'] = url($path, $options);
  }
}
