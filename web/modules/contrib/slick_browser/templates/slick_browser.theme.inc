<?php

/**
 * @file
 * Hooks and preprocess functions for the Slick Browser module.
 */

use Drupal\Core\Render\Element;
use Drupal\Component\Serialization\Json;

/**
 * Prepares variables for slick-browser.html.twig templates.
 *
 * @todo: Core image, Focal Point, Crop integration outside EB.
 */
function template_preprocess_slick_browser(&$variables) {
  $slick             = \Drupal::service('slick_browser');
  $element           = $variables['element'];
  $dynamic_id        = $element['#id'];
  $draggables        = $thumbs = $actions = [];
  $content           = [];
  $build             = [];
  $build['settings'] = $element['#settings'];
  $settings          = &$build['settings'];
  $field_name_css    = str_replace('_', '-', $settings['field_name']);
  $settings['id']    = 'sb-slick-' . $field_name_css;
  $settings['_text'] = !empty($settings['alt_field']) || !empty($settings['title_field']);

  // Get our list of widgets in order (needed when the form comes back after
  // preview or failed validation).
  $widgets = [];
  $element = isset($element['items']) && !empty($element['items']) ? $element['items'] : $element;
  foreach (Element::children($element) as $key) {
    $widgets[] = &$element[$key];
  }

  $settings['plugin_id'] = empty($settings['plugin_id']) ? '' : $settings['plugin_id'];
  if ($settings['plugin_id'] != 'entity_browser_entity_reference') {
    usort($widgets, '_field_multiple_value_form_sort_helper');
  }

  $count = count($widgets);

  $settings['count']   = $count;
  $variables['upload'] = isset($element['#file_upload_delta']);

  // This is because empty file upload is counted one.
  if ($variables['upload']) {
    $settings['count'] = $count > 1 ? ($count - 1) : $count;
  }

  // Vanilla is on, rebuild own logic to support asnavfor as needed.
  $settings['vanilla']     = TRUE;
  $settings['display']     = 'main';
  $settings['item_id']     = 'slide';
  $settings['iframe_lazy'] = TRUE;
  $settings['blazy']       = TRUE;
  $settings['lazy']        = 'blazy';
  $settings['nav']         = !empty($settings['optionset_thumbnail']) && $settings['count'] > 1;
  $settings['_grid_plain'] = FALSE;
  $settings['skin_widget'] = empty($settings['skin']) ? '' : $settings['skin'];

  if ($settings['style'] == 'grid' || $settings['style'] == 'column') {
    $settings['unslick']     = TRUE;
    $settings['_grid_plain'] = TRUE;

    // Provides sensible defaults for the ignorant who doesn't provide Grid as
    // otherwise confusingly invisible items.
    if (empty($settings['grid'])) {
      $settings['grid'] = 3;
      $settings['grid_medium'] = 2;
      $settings['grid_small'] = 1;
    }
  }

  $end = $variables['upload'] ? ($count - 2) : ($count - 1);
  if ($settings['count'] > 1) {
    $variables['attributes']['data-end'] = $end;
    $build['options']['initialSlide'] = $end;
  }

  // Defines ID if not provided.
  $variables['attributes']['id'] = $dynamic_id;
  $variables['attributes']['data-drupal-selector'] = $dynamic_id;

  $settings['_eb'] = FALSE;
  if ($settings['plugin_id'] && in_array($settings['plugin_id'], ['entity_browser_entity_reference', 'entity_browser_file'])) {
    $settings['_eb'] = TRUE;
    $settings['_browser'] = TRUE;
  }

  // Pass the settings to template.
  $item_id      = $settings['item_id'];
  $weight_class = $dynamic_id . '-weight';
  $sortable     = empty($settings['grid']) && $settings['count'] > 1;

  foreach ($widgets as $delta => &$widget) {
    // EB uses 'display' while core Image 'preview', get them consistent here.
    if ($settings['_eb']) {
      if (isset($widget['display'])) {
        $widget['preview'] = $widget['display'];
        unset($widget['display']);
      }
      if (isset($widgets[$delta]['#attributes']['class']) && is_array($widgets[$delta]['#attributes']['class'])) {
        $widgets[$delta]['#attributes']['class'] = array_diff($widgets[$delta]['#attributes']['class'], ['item-container', 'draggable']);
      }
    }
    else {
      // Save the uploading row for last, for image widget, not entity browser.
      if (isset($widget['#files']) && empty($widget['#files'])) {
        if (isset($element['#file_upload_title'])) {
          $widget['#title'] = $element['#file_upload_title'];
          $widget['#description'] = $slick->manager()->getRenderer()->renderPlain($element['#file_upload_description']);
        }

        foreach (Element::children($widget) as $sub_key) {
          if (!in_array($sub_key, ['display', 'fids', 'upload', 'upload_button'])) {
            unset($widget[$sub_key]);
          }
        }

        continue;
      }
    }

    // Merge with item settings.
    $item_settings = isset($widget['preview']['#settings']) ? array_merge($settings, $widget['preview']['#settings']) : $settings;

    // Adds thumbnail elements for each entity.
    if (!empty($item_settings['uri'])) {
      if (empty($widget['preview']['#uri'])) {
        $widget['preview']['#uri'] = $item_settings['uri'];
      }

      // Add small thumbnails for either asNavFor, or custom draggable elements.
      $item_settings['thumbnail_style'] = empty($item_settings['thumbnail_style']) ? 'slick_browser_thumbnail' : $item_settings['thumbnail_style'];
      // @todo provide ImageItem.
      $item = isset($widget['preview']['#item']) ? $widget['preview']['#item'] : NULL;
      $widget['thumb'] = $slick->formatter()->getThumbnail($item_settings, $item);
    }

    // Self-closed elements cannot be iconized, add the wrappers.
    foreach (['preview_link', 'remove_button', 'edit_button'] as $button) {
      if (isset($widget[$button])) {
        $slick::wrapButton($widget[$button], $button);
      }
    }

    // Delay rendering of the weight selector, so that can be rendered later.
    if (isset($widget['_weight'])) {
      if (empty($settings['grid'])) {
        hide($widget['_weight']);
      }
      $widget['_weight']['#attributes']['class'] = [$weight_class, 'sb__weight'];
      $widget['_weight']['#attributes']['data-slick-index'] = $delta;
      $widget['_weight']['#wrapper_attributes']['class'][] = 'visually-hidden';
    }

    if (isset($widget['thumb'])) {
      hide($widget['thumb']);
      $thumbs[] = $widget['thumb'];
    }

    // Arrange the row without the normal form_element wrappers.
    unset($widget['#theme'], $widget['#theme_wrappers']);

    // Makes grids draggable.
    if (!empty($settings['grid'])) {
      $widget['#attributes']['class'][] = 'sb__sortitem js-form-managed-file form-managed-file';
    }

    // Build the slides.
    $build['items'][$delta] = $widget;

    // We don't do early rendering, hide em all now.
    foreach (Element::children($widget) as $sub_key) {
      hide($widget[$sub_key]);
    }

    // Place _weight and thumb into sortable element.
    $draggable = [];
    if (isset($widget['_weight']) && empty($settings['grid'])) {
      show($widget['_weight']);
      $draggable[] = $widget['_weight'];
      unset($widget['_weight']);
    }

    // Provides the draggable thumbnails, if any.
    if (isset($widget['thumb'])) {
      if ($sortable) {
        show($widget['thumb']);
        $draggable[] = $widget['thumb'];
      }

      unset($widget['thumb']);
    }

    if ($sortable && $draggable) {
      if (!isset($widget['thumb']) && isset($widget['filename'])) {
        // At least we need something for custom draggable elements.
        $filename = is_string($widget['filename']) ? $widget['filename'] : $widget['filename']['#markup'];
        $draggable[] = ['#markup' => '<span class="slide__filename">' . $filename . '</span>'];
      }

      $attributes = [];
      if (isset($widget['#attributes'])) {
        $attributes = $widget['#attributes'];
      }

      $attributes['class'][] = 'slide sb__sortitem';
      if (!isset($attributes['data-row-id'])) {
        $attributes['data-row-id'] = $delta;
      }

      $draggables[] = [
        '#markup' => $slick->manager()->getRenderer()->render($draggable),
        '#wrapper_attributes' => $attributes,
      ];
    }
  }

  // Build the thumbnails.
  if ($settings['nav'] && $thumbs) {
    foreach ($thumbs as $delta => &$thumb) {
      $slide = [];

      show($thumb);

      $slide[$item_id]['#markup'] = $slick->manager()->getRenderer()->render($thumb);
      $build['thumb']['options']['initialSlide'] = $end;
      $build['thumb']['items'][$delta] = $slide;
      unset($slide);
    }
  }
  else {
    $settings['nav'] = FALSE;
  }

  // Build buttons.
  $buttons = [];
  if (isset($settings['crop_list'])) {
    $buttons['crop'] = 'Crop';
  }

  if ($settings['cardinality'] != 1) {
    if ($settings['_text']) {
      $buttons['caption'] = 'Text';
    }

    $buttons['removeall'] = 'Remove all';
  }

  // Build draggable.
  $content['draggable'] = [];
  if ($draggables) {
    $content['draggable'] = [
      '#theme' => 'item_list',
      '#items' => $draggables,
      '#wrapper_attributes' => [
        'class' => ['sb__sortlist'],
      ],
      '#attributes' => [
        'class' => ['sb__sortable', 'clearfix'],
      ],
    ];

    $buttons += [
      'sort' => 'Sort',
      'done' => 'Done',
    ];
  }

  if ($buttons) {
    foreach ($buttons as $key => $title) {
      $actions[$key . '_button'] = [
        '#type' => 'button',
        '#value' => t('@title', ['@title' => $title]),
        '#submit' => [],
        '#attributes' => [
          'class' => ['button--sb', 'button--js', 'button--' . $key],
          'data-target' => $key,
        ],
      ];
    }
  }

  // Pass variables to template.
  $content['header'] = $content['main'] = [];
  if (!empty($build['items'])) {
    $slick->formatter()->cleanUpBreakpoints($settings);

    // Provide Blazy attributes for the containing element.
    $blazy_data = '';
    $preview = isset($build['items'][0]['preview']) ? $build['items'][0]['preview'] : [];

    if (!empty($preview['#uri'])) {
      $item = isset($preview['#item']) ? $preview['#item'] : NULL;
      $settings['uri'] = $preview['#uri'];

      // Supports multi-serving images.
      $slick->formatter()->buildDataBlazy($settings, $item);
      $blazy_data = empty($settings['blazy_data']) ? '' : $settings['blazy_data'];
      $build['settings']['blazy_data'] = $blazy_data;
    }

    switch ($settings['style']) {
      case 'column':
      case 'grid':
        $grids = $slick->manager()->buildGrid($build['items'], $settings)[0]['slide'];
        $grids['#attributes']['class'][] = 'blazy';
        $grids['#attributes']['data-blazy'] = $blazy_data ? Json::encode($blazy_data) : '';

        $content['main'] = $grids;

        // Prevents collapsed details from breaking lazyload.
        $load = $slick->manager()->attach($settings);
        $load['drupalSettings']['blazy']['loadInvisible'] = TRUE;
        $content['main']['#attached'] = $load;
        break;

      case 'single':
      case 'slick':
        $content['main'] = $slick->manager()->build($build);
        break;
    }

    $content['header'] = $actions;
  }

  unset($element['#theme_wrappers']);

  $variables['content'] = $content;
  $variables['settings'] = $settings;
  $variables['element'] = $element;
}

/**
 * Prepares variables for slick-vanilla--browser.html.twig templates.
 */
function template_preprocess_slick_vanilla__browser(&$variables) {
  $settings = $variables['settings'];
  $item = $variables['item'];

  if (empty($item)) {
    return;
  }

  $attributes = &$variables['attributes'];
  if (!isset($attributes['data-row-id'])) {
    $attributes['data-row-id'] = $variables['delta'];
  }

  // Group item for easy placement while considering the different
  // structures between core Image and Entity browser.
  $variables['item'] = [];
  foreach (Element::children($item) as $child) {
    $sub_item[$child] = &$item[$child];

    $fid = 0;
    if (in_array($child, ['upload', 'upload_button'])) {
      unset($sub_item[$child]);
    }

    foreach (['display_field', 'preview_link', 'edit_button', 'remove_button'] as $sub_key) {
      if (isset($sub_item[$sub_key])) {
        $variables['item']['action'][$sub_key] = $sub_item[$sub_key];
        unset($sub_item[$sub_key]);
      }
    }

    if (!empty($settings['grid']) && isset($sub_item['_weight'])) {
      show($sub_item['_weight']);
      $variables['item']['action']['_weight'] = $sub_item['_weight'];
      unset($sub_item['_weight']);
    }

    if (isset($sub_item['meta'])) {
      // Must not use show() to avoid dup.
      $variables['item']['meta'] = $sub_item['meta'];
      unset($sub_item['meta']);
    }

    if (isset($sub_item['#files'])) {
      $file = reset($sub_item['#files']);
      $fid = $file->id();
      $variables['item']['meta']['file_' . $fid]['filename']['#suffix'] = '<span class="file-size"> [' . format_size($file->getSize()) . ']</span> ';
    }

    $meta = [
      'fids',
      'focal_point',
      'width',
      'height',
      'alt',
      'title',
      'file_' . $fid,
      'filename',
    ];
    foreach ($meta as $sub_key) {
      if (isset($sub_item[$sub_key])) {
        $variables['item']['meta'][$sub_key] = $sub_item[$sub_key];
        unset($sub_item[$sub_key]);
      }
    }

    if (isset($sub_item[$child])) {
      $variables['item'][$child] = $sub_item[$child];
      unset($sub_item[$child]);
    }
  }
}
