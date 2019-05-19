<?php

/**
 * @file
 * Preprocess for widget_engine module.
 */

use Drupal\Core\Render\Element;

/**
 * Implements hook_theme().
 */
function widget_engine_theme($existing, $type, $theme, $path) {
  $theme = [];
  $theme['widget'] = array(
    'render element' => 'elements',
    'template' => 'widget',
  );
  $theme['widget_content_add_list'] = [
    'render element' => 'content',
    'variables' => ['content' => NULL],
  ];
  $theme['html__widget_engine'] = [
    'template' => 'html--widget-engine',
    'render element' => 'html',
    'preprocess functions' => ['template_preprocess_html'],
  ];
  $theme['page__widget_engine'] = [
    'template' => 'page--widget-engine',
    'render element' => 'html',
    'preprocess functions' => ['template_preprocess_page'],
  ];

  return $theme;
}

/**
 * Prepares variables for Widget templates.
 *
 * Default template: widget.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_widget(array &$variables) {
  // Fetch Widget Entity Object.
  // $widget = $variables['elements']['#widget'];.
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function widget_engine_theme_suggestions_widget(array $variables) {
  $suggestions = [];
  $entity = $variables['elements']['#widget'];
  $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');

  $suggestions[] = 'widget__' . $sanitized_view_mode;
  $suggestions[] = 'widget__' . $entity->bundle();
  $suggestions[] = 'widget__' . $entity->bundle() . '__' . $sanitized_view_mode;
  $suggestions[] = 'widget__' . $entity->id();
  $suggestions[] = 'widget__' . $entity->id() . '__' . $sanitized_view_mode;
  return $suggestions;
}
