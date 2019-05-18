<?php

/**
 * @file
 * Theme functions for dynamic_persistent_menu.
 */

/**
 * Theme functions
 */
function theme_dynamic_persistent_menu($args) {
  $id = $args['id'];
  $menu_name = $args['menu'];
  $mlid = $args['parent'];
  $timeout = $args['timeout'];
  $submenu = '';

  global $language;
  static $settings;

  if (!isset($settings)) {
    $settings = array(
      "menus" => array()
    );
    $settings_js = drupal_json_encode($settings);
    // #D7UP Why do we need that drupal_add_js call?
    //drupal_add_js("DynamicPersistentMenu =  $settings_js;", array('type' => 'inline', 'scope' => JS_DEFAULT));
  }
  $item_class = "dynamic-persistent-menu-menu-item";
  $sub_item_class = "dynamic-persistent-menu-sub-menu-item";

  // Find menu item in the menu tree

  $tree = dynamic_persistent_menu_tree_page_data($menu_name, $mlid);

  // Pull out just the menu items we are going to render so that we
  // get an accurate count for the first/last classes.

  foreach ($tree as $data) {
    if (!$data['link']['hidden']) {
      $menu_tree[] = $data;
    }
  }

  $menu_link = menu_link_load($mlid);

  // Don't display anything if the selected menu has no children
  if (!isset($menu_tree) || !$menu_tree) {
    return;
  }

  // Build the menus
  $output = '<ul id="dynamic-persistent-menu-' . $id . '" style="display:block" class="dynamic-persistent-menu-menu">';

  $num_items = count($menu_tree);
  $k = 0;

  foreach ($menu_tree as $menu_item) {
    $link = $menu_item['link'];
    if ($link['hidden'] == 0 && (empty($link['options']['langcode']) || $link['options']['langcode'] == $language->language)) {
      if ($link['has_children']) {
        if ($link['in_active_trail']) {
          $display = 'block';
          $over_menu_default = $link['mlid'];
        }
        else {
          $display = 'none';
        }
      }

      if ($k == 0) {
        $extra_class = 'first';
      }
      if ($k == $num_items - 1) {
        $extra_class = 'last';
      }

      $output .= theme('dynamic_persistent_menu_menu_item', array('link' => $link, 'extra_class' => 'dynamic-persistent-menu-menu-item ' . $extra_class, 'id' => 'dynamic-persistent-menu-menu' . $link['mlid']));
      $extra_class = '';

      if ($menu_item['link']['has_children'] && is_array($menu_item['below'])) {
        $submenu_items ='';

        $num_sub_items = 0;
        foreach ($menu_item['below'] as $submenu_item) {
          if ($submenu_item['link']['hidden'] == 0) {
            $num_sub_items++;
          }
        }
        $i = 0;

        foreach ($menu_item['below'] as $submenu_item) {
          $link2 = $submenu_item['link'];
          if ($link2['hidden'] == 0) {
            if ($i == 0) {
              $extra_class = 'first';
            }
            if ($i == $num_sub_items - 1) {
              $extra_class = 'last';
            }
            $submenu_items .= theme('dynamic_persistent_menu_menu_item', array('link' => $link2, 'extra_class' => 'dynamic-persistent-menu-sub-menu-item ' . $extra_class, 'id' => 'dynamic-persistent-menu-sub-menu-item' . $link2['mlid']));
            $i++;
          }
          $extra_class = '';
        }

        if ($submenu_items !='') {
          $submenu .= '<ul class="dynamic-persistent-menu-sub-menu" id="dynamic-persistent-menu-sub-menu' . $link['mlid'] . '" style="display:' . $display . '">';
          $submenu .= $submenu_items;
          $submenu .= '</ul>';
        }
      }
    }
    $k++;
  }
  $output .= '</ul>';
  $output .= $submenu;
  $menu = array(
    'default' => isset($over_menu_default) ? $over_menu_default : NULL,
    'over' => isset($over_menu_default) ? $over_menu_default : NULL,
    'timeout' => $timeout,
  );
  drupal_add_js(array("dynamic_persistent_menu" => array("menus" => array($id => $menu))), 'setting');
  drupal_add_js(drupal_get_path('module', 'dynamic_persistent_menu') . '/dynamic_persistent_menu.js');
  return $output;
}

/**
 * Generate the HTML output for a menu item and/or submenu item.
 *
 * @ingroup themeable
 */
function theme_dynamic_persistent_menu_menu_item($args) {
  $link = $args['link'];
  $extra_class = $args['extra_class'];
  $id = $args['id'];
  $class = '';
  if (!empty($extra_class)) {
    $class .= ' ' . $extra_class;
  } 
  else {
    $class = '';
  }

  if ($link['in_active_trail']) {
    $link['localized_options']['attributes']['class'] = array('active');
  }
  $ret = theme('menu_link', array('element' => array('#title' => $link['title'], '#below' => NULL, '#href' => $link['href'], '#localized_options' => $link['localized_options'], '#attributes' => array('class' => $class, 'id' => $id)))) . "\n";
  return $ret;
}
