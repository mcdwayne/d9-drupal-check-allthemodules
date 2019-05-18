<?php

/**
 * @file
 * Implement a fine image field, based on the file module's file field.
 */

use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Random;

/**
 * Prepares variables for fine image widget templates.
 *
 * Default template: fine-image-widget.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the image field widget.
 */
function template_preprocess_fine_image_widget(array &$variables) {
  $element = $variables['element'];

  $variables['attributes'] = [
    'class' => [
      'fine-image-widget',
      'js-form-managed-file',
      'form-managed-file',
      'clearfix',
    ],
  ];

  $variables['data'] = [];

  // Add unique id to the field widget.
  $random = new Random();
  $variables['data']['id'] = 'fiu-id-' . $random->string(4);

  foreach (Element::children($element) as $child) {
    $variables['data'][$child] = $element[$child];

    if ($child == 'alt' || $child == 'title') {
      $variables['data'][$child]['#attributes']['data-item-number'] =
        $element['#delta'];
    }

    // Remove file name.
    if (strpos($child, 'file_') !== FALSE &&
      isset($variables['data'][$child]['filename'])) {
      $fileData = $variables['data'][$child]['filename'];

      $variables['file_name'] = _fiu_get_file_name($fileData, $element);

      if ($file = $fileData['#file']) {
        $variables['info']['mime'] = [
          'title' => t('Image mime type'),
          'value' => $file->getMimeType(),
        ];
        $variables['info']['size'] = [
          'title' => t('Image size'),
          'value' => $file->getSize(),
        ];
        $variables['info']['name'] = [
          'title' => t('Image file name'),
          'value' => $file->getFilename(),
        ];

        $variables['info']['url'] = [
          'title' => t('Image url'),
          'value' => file_create_url($file->getFileUri()),
        ];
      }

      unset($variables['data'][$child]['filename']);
    }
  }

  if (isset($variables['data']['width']['#value'])) {
    $variables['info']['width'] = [
      'title' => t('Image width'),
      'value' => $variables['data']['width']['#value'],
    ];
  }
  if (isset($variables['data']['height']['#value'])) {
    $variables['info']['height'] = [
      'title' => t('Image height'),
      'value' => $variables['data']['height']['#value'],
    ];
  }

  if (!empty($element['#errors']) &&
    !empty($element['#value']['fids'])) {
    $variables['attributes']['class'][] = 'fiu-broken-file';
    unset($variables['info']);
  }

  if ($element['#cardinality'] == 1 &&
    !empty($variables['element']['#value']['fids'])) {
    $variables['attributes']['class'][] = 'fiu-single-item';
  }

  // Add details button.
  if (!empty($variables['element']['#value']['fids'])) {
    $variables['details'] = t('details');
  }
}

/**
 * Prepares variables for multi fine image file form widget templates.
 *
 * Default template: fine-image-widget-multiple.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the widgets.
 */
function template_preprocess_fine_image_widget_multiple(array &$variables) {
  $element = $variables['element'];

  // Get our list of widgets in order (needed when the form comes back after
  // preview or failed validation).
  $widgets = [];
  foreach (Element::children($element) as $key) {
    $widgets[] = &$element[$key];
  }
  usort($widgets, '_field_multiple_value_form_sort_helper');

  $list = [];
  foreach ($widgets as $key => &$widget) {
    // Save the uploading row for last.
    if (empty($widget['#files'])) {
      $widget['#title'] = $element['#file_upload_title'];
      $widget['#description'] = \Drupal::service('renderer')->renderPlain($element['#file_upload_description']);
      continue;
    }

    if ($element['#display_field']) {
      hide($widget['display']);
    }
    hide($widget['_weight']);

    $widget['#theme_wrappers'] = [];
    $information = \Drupal::service('renderer')->render($widget, FALSE);
    $display = '';
    if ($element['#display_field']) {
      unset($widget['display']['#title']);
      $display = [
        'data' => render($widget['display']),
        'class' => ['checkbox'],
      ];
    }
    if (isset($widget['_weight']['#title'])) {
      unset($widget['_weight']['#title']);
    }
    $weight = render($widget['_weight']);

    $row = [];
    $row[] = $information;
    if ($element['#display_field']) {
      $row[] = $display;
    }
    $row[] = $weight;

    $list[] = [
      '#type' => 'inline_template',
      '#template' => implode('', $row),
    ];
  }

  $variables['list'] = [
    '#theme' => 'item_list',
    '#items' => $list,
    '#attributes' => [
      'id' => 'sortable',
      'class' => ['fiu-sortable-list'],
    ],
    '#access' => !empty($list),
  ];

  if (!isset($element['#file_upload_description']['#markup']) ||
    empty($element['#file_upload_description']['#markup'])) {
    $element = '';
  }

  $variables['id'] = $element['#id'];
  $variables['element'] = $element;
}

/**
 * Prepares variables for unitary fine image file form widget templates.
 *
 * Default template: fine-image-widget-unitary.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the widgets.
 */
function template_preprocess_fine_image_widget_unitary(array &$variables) {

}

/**
 * Helper function returns file name or file dir + file name.
 *
 * @param array $fileData
 *   Array with file data.
 * @param array $element
 *   Array with element data.
 *
 * @return string
 *   File name or dir + file name.
 */
function _fiu_get_file_name(array $fileData, array $element) {
  $file_name = '';

  if (isset($element['#fiu_show_file_name']) && $file = $fileData['#file']) {
    // @var \Drupal\file\Entity\File $file.
    switch ($element['#fiu_show_file_name']) {
      case 'file_name':
        $file_name = $file->getFilename();
        break;

      case 'file_dir':
        $fs = \Drupal::service('file_system');
        $dir = $fs->realpath($file->getFileUri());
        if ($dir) {
          $file_name = str_replace(DRUPAL_ROOT, "", $dir);
        }
        else {
          $file_name = $file->getFilename();
        }
        break;
    }
  }

  return $file_name;
}
