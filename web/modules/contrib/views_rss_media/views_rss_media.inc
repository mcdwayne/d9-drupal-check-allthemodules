<?php

/**
 * @file
 * Preprocess functions for Views RSS: Media Elements module.
 */

/**
 * Preprocess function for item <media:content> element.
 */
function views_rss_media_preprocess_item_content(&$variables) {
  // No raw value = no preprocessing.
  if (empty($variables['raw']['items'])) {
    return;
  }

  $library = libraries_load('getID3');
  if (!empty($library['loaded'])) {
    $getID3 = new getID3;
  }

  $variables['elements'] = array();
  foreach ($variables['raw']['items'] as $item) {
    // File fields.
    if (!empty($item['rendered']['#file'])) {
      $file = $item['rendered']['#file'];
    }
    // Image fields.
    if (!empty($item['rendered']['#item']->entity)) {
      $file = $item['rendered']['#item']->entity;
    }

    // Start building RSS element.
    $element = array(
      'key' => 'media:content',
      'attributes' => array(),
    );

    // File entity found.
    if (!empty($file)) {
      $mime_type = $file->getMimeType();
      // Image style is defined, need to link to resized version.
      if ($image_style_name = $item['rendered']['#image_style']) {
        $image_style = entity_load('image_style', $image_style_name);
        $uri = $image_style->buildUri($file->getFileUri());
        $element['attributes'] = array(
          'url' => $uri,
          'fileSize' => filesize(drupal_realpath($uri)),
          'type' => $mime_type,
        );
      }
      // Normal image size.
      else {
        $element['attributes'] = array(
          'url' => $file->url(),
          'fileSize' => $file->getSize(),
          'type' => $mime_type,
        );
      }

      list($medium) = explode('/', $mime_type);
      $element['attributes']['medium'] = $medium;

//      if (count($variables['raw']['items') > 1) {
//        $element['attributes']['isDefault'] = ($delta == 0) ? 'true' : 'false';
//      }

      if (!empty($library['loaded'])) {
        $path = drupal_realpath($file->getFileUri());
        $id3_info = $getID3->analyze($path);

        // Video details.
        if (!empty($id3_info['video']['frame_rate'])) {
          $element['attributes']['framerate'] = $id3_info['video']['frame_rate'];
        }
        if ($medium == 'video' && !empty($id3_info['video']['bitrate'])) {
          $element['attributes']['bitrate'] = $id3_info['video']['bitrate'];
        }
        if (!empty($id3_info['video']['resolution_x'])) {
          $element['attributes']['width'] = $id3_info['video']['resolution_x'];
        }
        if (!empty($id3_info['video']['resolution_y'])) {
          $element['attributes']['height'] = $id3_info['video']['resolution_y'];
        }

        // Audio details.
        if ($medium == 'audio' && !empty($id3_info['audio']['bitrate'])) {
          $element['attributes']['bitrate'] = $id3_info['audio']['bitrate'];
        }
        if (!empty($id3_info['audio']['sample_rate'])) {
          $element['attributes']['samplingrate'] = $id3_info['audio']['sample_rate'] / 1000;
        }
        if (!empty($id3_info['audio']['channels'])) {
          $element['attributes']['channels'] = $id3_info['audio']['channels'];
        }

        // Misc details.
        if (!empty($id3_info['playtime_seconds'])) {
          $element['attributes']['duration'] = (int) $id3_info['playtime_seconds'];
        }
      }
    }
    // No file entity found, but something still was assigned to be displayed
    // as enclosure, so we just put its value in the url attribute.
    elseif (!empty($item['rendered']['#markup'])) {
      $element['attributes']['url'] = $item['rendered']['#markup'];
    }

    $variables['elements'][] = $element;
  }

//  if (count($variables['elements']) > 1) {
//    $variables['elements'] = array(array(
//      'key' => 'media:group',
//      'value' => $variables['elements'],
//    ));
//  }
}

/**
 * Preprocess function for item <media:title> and <media:description> elements.
 */
function views_rss_media_preprocess_item_text(&$variables) {
  foreach ($variables['elements'] as $delta => $element) {
    if (!empty($element['value'])) {
      $type = 'plain';
      $value_decoded = htmlspecialchars_decode($element['value'], ENT_QUOTES);
      if ($value_decoded != strip_tags($value_decoded)) {
        $type = 'html';
      }
      $variables['elements'][$delta]['attributes']['type'] = $type;
    }
  }
}

/**
 * Preprocess function for item <enclosure> element.
 */
function views_rss_media_preprocess_item_thumbnail(&$variables) {
  // No raw values = no preprocessing.
  if (empty($variables['raw']['items'])) {
    return;
  }

  $library = libraries_load('getID3');
  if (!empty($library['loaded'])) {
    $getID3 = new getID3;
  }

  $variables['elements'] = array();
  foreach ($variables['raw']['items'] as $item) {
    // File fields.
    if (!empty($item['rendered']['#file'])) {
      $file = $item['rendered']['#file'];
    }
    // Image fields.
    if (!empty($item['rendered']['#item']->entity)) {
      $file = $item['rendered']['#item']->entity;
    }

    // Start building RSS element.
    $element = array(
      'key' => 'media:thumbnail',
      'attributes' => array(),
    );

    // File entity found.
    if (!empty($file)) {
      // Image style is defined, need to link to resized version.
      if ($image_style_name = $item['rendered']['#image_style']) {
        $image_style = entity_load('image_style', $image_style_name);
        $element['attributes']['url'] = $image_style->buildUri($file->getFileUri());
      }
      // Normal image size.
      else {
        $element['attributes']['url'] = $file->url();
      }

      if (!empty($library['loaded'])) {
        $path = drupal_realpath($file->getFileUri());
        $id3_info = $getID3->analyze($path);

        if (!empty($id3_info['video']['resolution_x'])) {
          $element['attributes']['width'] = $id3_info['video']['resolution_x'];
        }
        if (!empty($id3_info['video']['resolution_y'])) {
          $element['attributes']['height'] = $id3_info['video']['resolution_y'];
        }
      }
    }
    // No file entity found, but something still was assigned to be displayed
    // as enclosure, so we just put its value in the url attribute.
    elseif (!empty($item['rendered']['#markup'])) {
      $element['attributes']['url'] = $item['rendered']['#markup'];
    }

    $variables['elements'][] = $element;
  }
}

/**
 * Preprocess function for item <media:category> element.
 *
 * @see views_rss_core_preprocess_item_category()
 */
function views_rss_media_preprocess_item_category(&$variables) {
  // No raw values = no preprocessing.
  if (empty($variables['raw']['items'])) {
    return;
  }

  $variables['elements'] = array();
  foreach ($variables['raw']['items'] as $item) {

    // According to RSS Advisory Board, the category's value should be
    // a slash-delimited string that identifies a hierarchical position
    // in the taxonomy.
    $values = array();
    // Load parent term objects (this includes original term as well).
    if ($parents = \Drupal::entityManager()->getStorage('taxonomy_term')->loadAllParents($item['raw']->entity->id())) {
      foreach ($parents as $parent) {
        $values[] = $parent->label();
      }
    }

    $element = array(
      'key' => 'media:category',
      'value' => implode('/', array_reverse($values)),
      'attributes' => array(
        'label' => $item['raw']->entity->label(),
      ),
    );

    // Drupal uses term URL for domain attribute on main category element
    // (similar to media:category). RSS Best Practices say that domain attribute
    // identifies the category's taxonomy - which suggests either vocabulary
    // name or its URL. We don't have any safe way to know public vocabulary
    // URL, could use its name instead though? @TODO?
    if ($item['raw']->target_id) {
      $element['attributes']['scheme'] = $item['raw']->entity->url('canonical', array('absolute' => TRUE));
    }

    $variables['elements'][] = $element;
  }
}
