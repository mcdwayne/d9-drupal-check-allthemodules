<?php
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Theme the sidebar nav for sms ui
 */
function theme_sms_ui_nav($tree = array(), $trail = array()) {
  $i = 0;
  $output = '';
  foreach ($tree as $item) {
    $i = $i % 4;
    $nextlevel = '';
    if (!empty($item['link']['has_children'])) {
      // We assume only one level of indentation, deeper levels are ignored
      foreach ($item['below'] as $s_item) {
        $s_item['link']['has_children'] = 0;
        $nextlevel .= recursive_menu_link_create($s_item, $trail);
      }
      if (!empty($nextlevel)) {
        $item['link']['has_children'] = 1;
      }
      else {
        $item['link']['has_children'] = 0;
      }
    }

    $nextlevel = recursive_menu_link_create($item, $trail) . $nextlevel;
    if ($nextlevel) {
      $output .= '<div class="menu-' . $i . ' block-inner"><ul>';
      $output .= $nextlevel;
      $output .= '</ul></div>';
      $i++;
    }
  }
  return $output;
}

function recursive_menu_link_create($item, $trail) {
  if (in_array($item['link']['href'], $trail)) {
    $classes[] = 'active-trail';
  }

  if ($_GET['q'] == $item['link']['href']) {
    $classes[] = 'active';
  }

  if ($item['link']['has_children']) {
    $classes[] = 'parent';
  }

  $options = array();
  if (isset($item['link']['options'])) {
    $options = $item['link']['options'];
  }

  // Just dubbed this section of code without check
  if (!empty($item['link']['to_arg_functions'])) {
    $toarg_array = array();
    $patharray = explode('/', $item['link']['href']);
    foreach ($patharray as $chunk) {
      if ($chunk != '%') {
        $toarg_array[] = $chunk;
      }
      else {
        $function = $item['link']['to_arg_functions'];
        $function = explode('"', $function);
        $function = $function[1];
        $toarg_array[] = $function('%');
      }
    }
    $path = implode('/', $toarg_array);
  }
  else {
    $path = $item['link']['href'];
  }

  $output = '';
  if ($item['link']['hidden'] != 1 && $item['link']['hidden'] != -1) {
    $output .= '<li'. (empty($classes) ? '>' : ' class="'. implode(' ', $classes) .'">');
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $output .= l($item['link']['title'], $path, $options);

    $output .= '</li>';
  }
  return $output;
}
