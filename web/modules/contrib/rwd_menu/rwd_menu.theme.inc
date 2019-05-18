<?php

/**
 * @file
 * Contains module theme implementations.
 */

use Drupal\Core\Render\Markup;

/**
 * Main rwd menu theme function.
 */
function template_preprocess_rwd_menu(&$variables) {
  $variables['content'] = Markup::create(_rwd_menu_theme($variables['items']));
  $variables['trigger'] = Markup::create('<a class="rwd-menu-trigger open" href="javascript:void(0);">' . t('Menu') . '</a>');
  $variables['#attached']['library'][] = 'rwd_menu/jquery.rwdMenu';
  if ($variables['css']) {
    $variables['#attached']['library'][] = 'rwd_menu/jquery.rwdMenu.appearance';
  }
}

/**
 * Slide type menu theme function.
 */
function _rwd_menu_theme($menu_tree, $include_trigger = TRUE, $level = 0, $parent = NULL) {
  $menu = '';

  $number = 0;
  $nelements = count($menu_tree);
  $menu_items = '';
  foreach ($menu_tree as $key => $element) {
    $number++;
    $below = !empty($element['below']);
    if ($below) {
      $submenu = _rwd_menu_theme($element['below'], FALSE, ($level + 1), $element);
    }
    if (empty($submenu)) {
      $below = FALSE;
    }

    // Set element classes.
    $element_classes = [];
    if ($number === 1) {
      $element_classes[] = 'first';
    }
    if ($number === $nelements) {
      $element_classes[] = 'last';
    }
    if ($number % 2 === 0) {
      $element_classes[] = 'even';
    }
    else {
      $element_classes[] = 'odd';
    }
    $element_classes[] = 'id-' . $element['original_link']->getBaseId();
    if ($below) {
      $element_classes[] = 'branch';
    }
    else {
      $element_classes[] = 'leaf';
    }
    if ($element['in_active_trail']) {
      $element_classes[] = 'active-trail';
    }

    $menu_items .= '<li class="' . implode(' ', $element_classes) . '"><a href="' . $element['url']->toString() . '">' . $element['title'] . '</a>';
    if ($below) {
      $menu_items .= $submenu;
    }
    $menu_items .= '</li>';
  }

  if ($menu_items) {
    if ($level === 0) {
      $menu .= '<a href="javascript:void(0)" class="rwd-menu-trigger close">' . t('Close') . '</a>';
    }

    $menu_classes = ['menu'];
    if ($level > 0) {
      $menu_classes[] = 'submenu';
    }
    $menu_classes[] = 'level-' . $level;
    $menu .= '<div class="' . implode(' ', $menu_classes) . '">';

    if ($level > 0) {
      if (!empty($parent['url'])) {
        $title = '<a href="' . $parent['url']->toString() . '">' . $parent['title'] . '</a>';
      }
      else {
        $title = $parent['title'];
      }
      $menu .= '<div class="top"><a href="javascript:void(0)" class="back">' . t('Back') . '</a><span class="title">' . $title . '</span></div>';
    }

    $menu .= '<ul>' . $menu_items . '</ul>';
  }
  return $menu;
}
