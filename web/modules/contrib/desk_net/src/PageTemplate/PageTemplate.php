<?php

/**
 * @file
 * The template for "Matching" pages.
 */

namespace Drupal\desk_net\PageTemplate;

use Drupal\desk_net\Controller\ModuleSettings;

class PageTemplate {
  /**
   * Perform generation Template for Matching page.
   *
   * The array structure
   *  array array[key]
   *   The key has ID or Slug value
   *   string array[key][name]
   *   The element Name
   *   string array[key][parent]
   *   The element Parent.
   *
   * @param array $element_name_list
   *   The main elements list for the first block.
   * @param array $element_value_list
   *   The main elements list for the second block.
   * @param string $type
   *   The type element.
   * @param string $send_direction
   *   The direction for send elements.
   * @param string $default_value
   *   The default value.
   *
   * @return string
   *   The html code page template.
   */
  public static function desk_net_matching_page_template(array $element_name_list, array $element_value_list, $type, $send_direction, $default_value = '') {
    foreach ($element_name_list as $key => $elementName) {
      $title = '';

      if (!empty(ModuleSettings::variableGet('desk_net_' . $type . '_' . $send_direction . '_' . $element_name_list[$key]['id']))) {
        $selected_value = ModuleSettings::variableGet('desk_net_' . $type . '_' . $send_direction . '_' . $element_name_list[$key]['id']);
      }
      else {
        $selected_value = $default_value;
      }

      if (isset($element_name_list[$key]['parent'])) {
        $parent_key = $element_name_list[$key]['parent'];
        foreach ($element_name_list as $element_key => $drupal_element) {
          if ($element_key == $parent_key) {
            $title .= $element_name_list[$element_key]['name'] . '<strong> - </strong>';
            break;
          }
        }
      }

      $title .= $element_name_list[$key]['name'];

      // Get parent for sub items.
      foreach ($element_value_list as $keys => $value) {
        if (isset($element_value_list[$keys]['parent'])) {
          $parent_id = $element_value_list[$keys]['parent'];
          foreach ($element_value_list as $element_key => $drupal_element) {
            if ($element_value_list[$element_key]['id'] == $parent_id) {
              $parent_name = $element_value_list[$element_key]['name'];
              break;
            }
          }
        }

        if (isset($parent_name) && !empty($parent_name)) {
          $element_value = $parent_name . ' - ' . $element_value_list[$keys]['name'];
          $parent_name = '';
        }
        else {
          $element_value = $element_value_list[$keys]['name'];
        }
        $option_list[$element_value_list[$keys]['id']] = $element_value;
      }
      $form['desk_net_' . $type . '_' . $send_direction . '_' . $element_name_list[$key]['id']] = array(
        '#type' => 'select',
        '#title' => $title,
        '#options' => $option_list,
        '#default_value' => $selected_value,
        '#required' => FALSE,
      );
    }

    return $form;
  }
}
