<?php

/**
 * @file
 * Photoswipe theme preprocess functions.
 */

use Drupal\Core\Language\Language;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;

/**
 * Prepares variables for a Photoswipe image field formatter.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - display_settings: optional image styles.
 *
 * @ingroup themeable
 */
function template_preprocess_photoswipe_image_formatter(array &$variables) {
  $item = $variables['item'];
  $settings = $variables['display_settings'];
  // Add support for Drupal Core Media and media_entity Media references:
  if (($item->entity instanceof Drupal\media\MediaInterface || $item->entity instanceof Drupal\media_entity\MediaInterface) && $item->entity->hasField($settings['photoswipe_reference_image_field'])) {
    $item = $item->entity->get($settings['photoswipe_reference_image_field']);
  }
  $entity = $variables['entity'];
  $uri = $item->entity->getFileUri();
  $alt = !empty($item->alt) ? $item->alt : '';
  $title = !empty($item->title) ? $item->title : '';

  if (empty($alt) && !empty($item->field_file_image_alt_text[Language::LANGCODE_NOT_SPECIFIED])) {
    $alt = $item->field_file_image_alt_text[Language::LANGCODE_NOT_SPECIFIED][0]['value'];
  }
  if (empty($title) && !empty($item->field_file_image_title_text[Language::LANGCODE_NOT_SPECIFIED])) {
    $title = $item->field_file_image_title_text[Language::LANGCODE_NOT_SPECIFIED][0]['value'];
  }
  $image = [
    '#theme' => 'image_style',
    '#uri' => $uri,
    '#alt' => $alt,
    '#title' => $title,
    '#attributes' => $item->_attributes,
    '#style_name' => $settings['photoswipe_node_style'],
  ];

  if (isset($variables['delta']) && $variables['delta'] === 0 && !empty($settings['photoswipe_node_style_first'])) {
    $image['#style_name'] = $settings['photoswipe_node_style_first'];
  }

  // The image.factory service will check if our image is valid.
  $image_file = \Drupal::service('image.factory')->get($uri);
  if ($image_file->isValid()) {
    $image_width = $image_file->getWidth();
    $image_height = $image_file->getHeight();
  }
  else {
    $image_width = $image_height = NULL;
  }

  $dimensions = [];
  if (!empty($image_width) && !empty($image_height)) {
    $image['#width'] = $dimensions['width'] = $image_width;
    $image['#height'] = $dimensions['height'] = $image_height;
  }

  // Create the path to the image that will show in Photoswipe.
  if ($style_name = $settings['photoswipe_image_style']) {
    // Load the image style.
    $style = ImageStyle::load($style_name);

    // Fetch the Image style path from the Image URI.
    $path = $style->buildUrl($uri);

    // Set the dimensions.
    $style->transformDimensions($dimensions, $uri);
  }
  else {
    $path = file_create_url($uri);
  }

  // Render as a standard image if an image style is not given.
  if (empty($image['#style_name']) || $image['#style_name'] === 'hide') {
    $image['#theme'] = 'image';
  }

  // Set Caption for this image.
  if (isset($settings['photoswipe_caption'])) {
    $caption_setting = $settings['photoswipe_caption'];
    switch ($caption_setting) {
      case 'alt':
        $caption = $alt;
        break;

      case 'title':
        $caption = $title;
        break;

      case 'node_title':
        if (!empty($entity->title)) {
          $caption = $entity->title->value;
        }
        else {
          $caption = $alt;
        }
        break;

      case 'custom':
        $entity_type = $entity->getEntityTypeId();
        $caption = \Drupal::token()->replace($settings['photoswipe_caption_custom'],
          [$entity_type => $entity, 'file' => $item],
          ['clear' => TRUE, 'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId()]);
        break;

      default:
        // Assume the user wants to use another node field as the caption.
        $field_view['#view_mode'] = ($settings['photoswipe_view_mode']) ? $settings['photoswipe_view_mode'] : 'default';
        if (!isset($entity->{$caption_setting})) {
          // No such field exists. We'd better warn and use something reliable.
          $id = $entity->id();
          $msg = "'Photoswipe Caption' is unset for field view '@fv' on node: @nid.";
          \Drupal::logger('photoswipe')->warning($msg, [
            '@fv' => $field_view['#view_mode'],
            '@nid' => $id,
          ]);
          // Fallback to alt text:
          $caption = $alt;
          break;
        }
        $field_view = $entity->{$caption_setting}->view();
        $caption = render($field_view);
        break;
    }
  }
  else {
    $caption = $alt;
  }

  $variables['image'] = $image;
  $variables['path'] = $path;
  $variables['attributes']['class'][] = 'photoswipe';
  $variables['attributes']['data-size'] = $dimensions['width'] . 'x' . $dimensions['height'];
  $variables['attributes']['data-overlay-title'] = $caption;
  if ($image['#style_name'] === 'hide') {
    // Do not display if hidden is selected:
    $variables['attributes']['class'][] = 'hidden';
  }
}
