<?php

/**
 * Implements hook_element_info_alter().
 */
function improvements_toolbar_element_info_alter(array &$elements) {
  if (isset($elements['toolbar'])) {
    $elements['toolbar']['#attached']['library'][] = 'improvements/toolbar';
  }
}

/**
 * Implements hook_toolbar_alter().
 */
function improvements_toolbar_alter(&$items) {
  if (isset($items['administration']['#attached']['library'])) {
    $items['administration']['#attached']['library'] = array_diff($items['administration']['#attached']['library'], [
      'admin_toolbar/toolbar.tree',
      'admin_toolbar_tools/toolbar.icon',
    ]);
  }

  foreach (['home', 'user', 'contextual', 'devel', 'admin_toolbar_tools'] as $item) {
    if (isset($items[$item])) {
      unset($items[$item]);
    }
  }
}

/**
 * Implements hook_theme_registry_alter().
 */
function improvements_theme_registry_alter(&$theme_registry) {
  $theme_registry['html']['preprocess functions'] = array_diff($theme_registry['html']['preprocess functions'], ['toolbar_preprocess_html']);
}

/**
 * Implements hook_preprocess_HOOK() for HTML document templates.
 */
function improvements_preprocess_html(&$variables) {
  if (\Drupal::currentUser()->hasPermission('access toolbar')) {
    $variables['attributes']['class'][] = 'page--toolbar';
  }
}
