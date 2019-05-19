<?php

/**
 * @file
 * Preprocess functions for SooperThemes GridStack module.
 */

use Drupal\Component\Serialization\Json;
use Drupal\image\Entity\ImageStyle;

/**
 * Prepares variables for gridstack formatter templates.
 *
 * Default template: sooperthemes-gridstack-gridstack-formatter.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - image_style: An optional image style.
 *   - url: An optional \Drupal\Core\Url object.
 */
function template_preprocess_sooperthemes_gridstack_gridstack_formatter(&$vars) {
  /** @var \Drupal\file\FileInterface $entity */
  $entity = $vars['item']->entity;
  $image_uri = $entity && empty($vars['item']->uri) ? $entity->getFileUri() : $vars['item']->uri;
  $vars['image_url'] = $vars['image_style'] ?
    ImageStyle::load($vars['image_style'])->buildUrl($image_uri) : file_create_url($image_uri);
}

/**
 * Implementation of template preprocess for the view style.
 */
function template_preprocess_sooperthemes_gridstack_gridstack_style(&$vars) {
  template_preprocess_views_view_unformatted($vars);

  $options  = $vars['view']->style_plugin->options;
  $layout_data = $options['gridstack_layout_data'];
  $vars['layout_data'] = Json::decode($layout_data);
  if ($options['gridstack_overlay']) {
    $vars['classes'][] = 'sooperthemes-gridstack--' . $options['gridstack_overlay'];
  }
  if ($options['gridstack_zoom']) {
    $vars['classes'][] = 'sooperthemes-gridstack--zoom';
  }

  if (($options['gridstack_gap'])) {
    $vars['gridstack_margin'] = 'style="margin: ' . round($options['gridstack_gap'] / 2, 2) . 'px"';
  }
  if (($options['gridstack_items_mobile'])) {
    // -1 because $key counts from 0.
    $vars['gridstack_items_mobile'] = $options['gridstack_items_mobile'] - 1;
  }
  else {
    $vars['gridstack_items_mobile'] = 2;
  }
}

/**
 * Implementation of template preprocess for the view row.
 */
function template_preprocess_sooperthemes_gridstack_gridstack_row(&$vars) {
  /** @var \Drupal\views\ViewExecutable $view */
  $view = &$vars['view'];

  $fields = &drupal_static(__FUNCTION__);
  if (!$fields) {
    $fields = $view->display_handler->getOption('fields');
  }

  foreach ($vars['options'] as $id => $field) {
    switch ($id) {
      case 'image':
      case 'title':
      case 'category':
        if (isset($fields[$field])
          && !$fields[$field]['exclude']) {
          $vars[$id] = $view->style_plugin->getField($vars['row']->index, $field);;
        }
        break;
    }
  }
}
