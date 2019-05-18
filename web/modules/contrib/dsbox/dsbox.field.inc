<?php

/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

use Drupal\Component\Utility\String;

/**
 * Prepares variables for the Swipebox image field template.
 *
 * @param array $variables
 *   An associative array containing:
 *   - image_original_uri: The stream URI to the uploaded image.
 *   - item: An array of image data.
 *   - item_filename: The image file name.
 *   - path: An array contains path and options.
 *           The path is the URI to the uploaded image.
 *   - image_style: An optional image style for the content.
 *   - swipebox_image_style: An optional image style for the swipebox.
 *   - swipebox_gallery: An value for the swipebox image grouping.
 *   - swipebox_gallery_custom: The custom value if the custom image grouping
 *                              used.
 *   - swipebox_caption: An value for the swipebox caption variant.
 *   - swipebox_caption_custom: The custom value if the custom caption option
 *                              used.
 *   - entity: Object. Examples: node
 *   - entity_type: The entity type machine name. Examples: node
 *   - field_definition: Object.
 *   - image: An associative array contains a collection of image data.
 *   - link: The link that has already been formatted by _l() and is usable
 *           with the Swipebox feature.
 *   - link_url: The image URL to the lightbox image. E.g
 *               http://www.foo.com/sites/default/files/styles/large/public/fooimages/i-2XMXCBm-O_0.jpg
 *   - link_options: An array contains the attributes array. The attributes
 *                   array contains:
 *                   - class: The class name to bind the swipebox behaviour
 *                            on a link.
 *                   - rel: The lightbox grouping value.
 *                   - title: The lightbox image caption.
 *
 *   Available variables in context with breakpoint mapping functions.
 *   Without this context, these variables are empty.
 *   - swipebox_fallback_image_style:
 *   - breakpoints: An array containing breakpoints.
 *
 * @ingroup themeable
 */
function template_preprocess_dsbox_image_formatter(&$variables) {
  $item = $variables['item'];
  $image = array();

  $style_defined = FALSE;
  if (!empty($variables['image_style'])) {
    $style_defined = TRUE;
  }
  $swipebox_style_defined = FALSE;
  if (!empty($variables['swipebox_image_style'])) {
    $swipebox_style_defined = TRUE;
  }

  // Do not output an empty 'title' attribute.
  $image['#title'] = empty($item->title) ? $variables['item_filename'] : $item->title;
  // Do not output an empty 'alt' attribute.
  $image['#alt'] = empty($item->alt) ? $variables['item_filename'] : $item->alt;

  $image['#uri'] = $item->entity->getFileUri();

  foreach (array('width', 'height') as $key) {
    if (isset($item->$key) || array_key_exists($key, (array) $item)) {
      $image["#$key"] = $item->$key;
    }
  }

  if ($style_defined) {
    $image['#theme'] = 'image_style';
    $image['#style_name'] = $variables['image_style'];
  }
  else {
    $image['#theme'] = 'image';
  }

  $image['#attributes'] = $variables['item_attributes'];

  // Prepare the URL for the image link.
  if ($swipebox_style_defined) {
    $style_url = dsbox_image_style_url($variables['swipebox_image_style'], $item);
    $style = _dsbox_get_image_style($variables['swipebox_image_style']);
    $style_uri = $style->buildUri($item->entity->getFileUri());

    // Generating the style-image if it does not exist.
    if (!file_exists($style_uri)) {
      $style->createDerivative($variables['image_original_uri'], $style_uri);
    }
  }
  else {
    $style_url = file_create_url($variables['image_original_uri']);
  }

  // Swipebox image grouping.
  switch ($variables['swipebox_gallery']) {
    case 'none':
      $gid = 'dsbox-gallery-' . rand();
      break;
    case 'post':
      if ($variables['field_definition']->entity_type == 'node') {
        $gid = 'dsbox-gallery-post-' . $variables['field_definition']->entity_type . '-' . $variables['parent_entity']->nid->value;
      }
      else {
        $gid = 'dsbox-gallery-post';
      }
      break;
    case 'page':
      $gid = 'dsbox-gallery-page';
      break;
    case 'entity_type':
      $gid = 'dsbox-gallery-entity-' . $variables['field_definition']->entity_type;
      break;
    case 'field':
      $gid = 'dsbox-gallery-field-' . $variables['field_definition']->uuid;
      break;
    case 'custom':
      $gid = $variables['swipebox_gallery_custom'] ? String::checkPlain($variables['swipebox_gallery_custom']) : 'dsbox-gallery-custom-no-value';
      break;
    default:
      $gid = 'dsbox-gallery';
      break;
  }

  // @todo Make token usage available for custom caption.
  //$token_service = \Drupal::service('token');
  //$custom = $token_service->replace($variables['swipebox_caption_custom']);

  // Swipebox image caption.
  $entity_title = '';
  if ($variables['field_definition']->entity_type == 'node') {
    $entity_title = $variables['parent_entity']->title->value;
  }
  switch ($variables['swipebox_caption']) {
    case 'none':
      $caption = '';
      break;
    case 'auto':
      if (!empty($item->title)) {
        $caption = String::checkPlain($item->title);
      }
      elseif (!empty($item->alt)) {
        $caption = String::checkPlain($item->alt);
      }
      else {
        $caption = String::checkPlain($entity_title);
      }
      break;
    case 'title':
      $caption = String::checkPlain($image['#title']);
      break;
    case 'alt':
      $caption = String::checkPlain($image['#alt']);
      break;
    case 'content_title':
      $caption = String::checkPlain($entity_title);
      break;
    case 'custom':
      $caption = String::checkPlain($variables['swipebox_caption_custom']);
      //token_replace($variables['swipebox_caption_custom'], array('entity' => $entity));
      break;
    default:
      $caption = '';
      break;
  }

  // The link path and link options are both optional, but for the options to be
  // processed, the link path must at least be an empty string.
  $link_options = isset($variables['path']['options']) ? $variables['path']['options'] : array();
  // When displaying an image inside a link, the html option must be TRUE.
  $link_options['html'] = TRUE;

  $link_options['attributes'] = array(
    'class' => array('dsbox'),
    'rel' => $gid,
    'title' => $caption
  );

  $variables['image'] = $image;

  $variables['link'] = _l($image, $style_url, $link_options);
  $variables['link_url'] = $style_url;
  $variables['link_options'] = $link_options;
}

/**
 * Prepares variables for Swipebox video link field template.
 *
 * @param array $variables
 *   An associative array containing:
 *   - link: The link that has already been formatted by _l() and is usable with
 *           the Swipebox feature.
 *   - linktext: The text to display the link. If the link text not available
 *               will be used the link URL.
 *   - linkurl: The complete URL to a video.
 *              E.g http://www.youtube.com/watch?v=XSGBVzeBUbk&hd=1
 *   - href: The complete URL to a video.
 *           E.g http://www.youtube.com/watch?v=XSGBVzeBUbk
 *   - options: An associative array contains link options such as
 *              - query: The v parameter with video ID.
 *              - fragment: Not used.
 *              - attributes: The attributes array contains:
 *                - class: The class name to bind the swipebox behaviour
 *                         on a link.
 *                - rel: The lightbox grouping value.
 *
 * @ingroup themeable
 */
function template_preprocess_dsbox_link_formatter_video_link(&$variables) {
  if (!empty($variables['linktext'])) {
    $variables['linktext'] = String::checkPlain($variables['linktext']);
  }

  $variables['linkurl'] = _url($variables['href'], $variables['options']);
  $variables['link'] = _l($variables['linktext'], $variables['href'], $variables['options']);
}
