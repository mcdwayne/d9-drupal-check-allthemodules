<?php

/**
 * @file
 * Callbacks and theming for the CKEditor toolbar configuration UI.
 */

use Drupal\Core\Template\Attribute;

/**
 * Preprocess variables for theme_ckeditor_settings_toolbar().
 */
function template_preprocess_ckeditor_settings_toolbar(&$variables) {
  // Simplify the language direction information for toolbar buttons.
  global $language;
  $variables['language_direction'] = isset($language->direction) && $language->direction === LANGUAGE_RTL ? 'rtl' : 'ltr';

  // Create lists of active and disabled buttons.
  $editor = $variables['editor'];
  $plugins = $variables['plugins'];
  $buttons = array();
  $variables['multiple_buttons'] = array();
  foreach ($plugins as $plugin) {
    if (isset($plugin['buttons'])) {
      foreach ($plugin['buttons'] as $button_name => $button) {
        if (!empty($button['multiple'])) {
          $variables['multiple_buttons'][$button_name] = $button;
        }
        $button['name'] = $button_name;
        $buttons[$button_name] = $button;
      }
    }
  }
  $variables['active_buttons'] = array();
  foreach ($editor->settings['toolbar']['buttons'] as $row_number => $row) {
    foreach ($row as $button_name) {
      if (isset($buttons[$button_name])) {
        $variables['active_buttons'][$row_number][] = $buttons[$button_name];
        if (empty($buttons[$button_name]['multiple'])) {
          unset($buttons[$button_name]);
        }
      }
    }
  }
  $variables['disabled_buttons'] = array_diff_key($buttons, $variables['multiple_buttons']);
}

/**
 * Displays the toolbar configuration for CKEditor.
 */
function theme_ckeditor_settings_toolbar($variables) {
  $editor = $variables['editor'];
  $plugins = $variables['plugins'];
  $rtl = $variables['language_direction'] === 'rtl' ? '_rtl' : '';

  // Assemble items to be added to active button rows.
  foreach ($variables['active_buttons'] as $row_number => $row_buttons) {
    foreach ($row_buttons as $button) {
      $button_name = $button['name'];
      if (isset($button['image_alternative'])) {
        $data = $button['image_alternative'];
      }
      elseif (isset($button['image'])) {
        $data = theme('image', array('uri' => $button['image' . $rtl], 'title' => $button['label']));
      }
      else {
        $data = '?';
      }
      $button_item = array(
        'data' => $data,
        'data-button-name' => $button_name,
      );
      if (!empty($button['multiple'])) {
         $button['attributes']['class'][] = 'ckeditor-multiple-button';
      }
      if (!empty($button['attributes'])) {
        $button_item = array_merge($button_item, $button['attributes']);
      }
      $active_buttons[$row_number][] = $button_item;
    }
  }
  // Assemble list of disabled buttons (which are always a single row).
  foreach ($variables['disabled_buttons'] as $button_name => $button) {
    if (isset($button['image_alternative'])) {
      $data = $button['image_alternative'];
    }
    elseif (isset($button['image'])) {
      $data = theme('image', array('uri' => $button['image' . $rtl], 'title' => $button['label']));
    }
    else {
      $data = '?';
    }
    $button_item = array(
      'data' => $data,
      'data-button-name' => $button_name,
    );
    if (isset($button['attributes'])) {
      $button_item = array_merge($button_item, $button['attributes']);
    }
    $disabled_buttons[] = $button_item;
  }
  // Assemble list of multiple buttons that may be added multiple times.
  foreach ($variables['multiple_buttons'] as $button_name => $button) {
    if (isset($button['image_alternative'])) {
      $data = $button['image_alternative'];
    }
    elseif (isset($button['image'])) {
      $data = theme('image', array('uri' => $button['image' . $rtl], 'title' => $button['label']));
    }
    else {
      $data = '?';
    }
    $button_item = array(
      'data' => $data,
      'data-button-name' => $button_name,
    );
    $button['attributes']['class'][] = 'ckeditor-multiple-button';
    if (isset($button['attributes'])) {
      $button_item = array_merge($button_item, $button['attributes']);
    }
    $multiple_buttons[] = $button_item;
  }

  // We don't use theme_item_list() below in case there are no buttons in the
  // active or disabled list, as theme_item_list() will not print an empty UL.
  $output = '';
  $output .= '<strong>' . t('Active toolbar') . '</strong>';
  $output .= '<div class="ckeditor-toolbar-active clearfix">';
  foreach ($active_buttons as $button_row) {
    $output .= '<ul class="ckeditor-buttons">';
    foreach ($button_row as $button) {
      $contents = $button['data'];
      unset($button['data']);
      $attributes = (string) new Attribute($button);
      $output .= '<li' . $attributes . '>' . $contents . '</li>';
    }
    $output .= '</ul>';
  }
  if (empty($active_buttons)) {
    $output .= '<ul class="ckeditor-buttons">';
    $output .= '</ul>';
  }

  $output .= '<div class="ckeditor-row-controls">';
  $output .= '<a href="#" class="ckeditor-row-remove" title="' . t('Remove row') . '">-</a>';
  $output .= '<a href="#" class="ckeditor-row-add" title="' . t('Add row') . '">+</a>';
  $output .= '</div>';

  $output .= '</div>';

  $output .= '<strong>' . t('Available buttons') . '</strong>';
  $output .= '<div class="ckeditor-toolbar-disabled clearfix">';
  $output .= '<ul class="ckeditor-buttons">';
  foreach ($disabled_buttons as $button) {
    $contents = $button['data'];
    unset($button['data']);
    $attributes = (string) new Attribute($button);
    $output .= '<li' . $attributes . '>' . $contents . '</li>';
  }
  $output .= '</ul>';
  $output .= '<strong class="ckeditor-multiple-label">' . t('Dividers') . ': </strong>';
  $output .= '<ul class="ckeditor-multiple-buttons">';
  foreach ($multiple_buttons as $button) {
    $contents = $button['data'];
    unset($button['data']);
    $attributes = (string) new Attribute($button);
    $output .= '<li' . $attributes . '>' . $contents . '</li>';
  }
  $output .= '</ul>';
  $output .= '</div>';

  return $output;
}
