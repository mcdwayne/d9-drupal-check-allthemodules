<?php

namespace Drupal\spectra\SpectraUtilities;

/**
 *
 *
 * @ingroup spectra
 */
class SpectraDataFields {

  /**
   ******************************************************************
   * Common Components for Views data viewer fields
   ******************************************************************
   */

  /**
   * @param $options
   * @return mixed
   */
  public static function DataViewerFormOptions($options) {
    $options['render_key'] = array('default' => '');
    $options['render_array'] = array('default' => 'print_r');
    $options['render_array_max'] = array('default' => '5');
    return $options;
  }

  /**
   * @param $form
   * @param $view_field
   */
  public static function DataViewerFormElements($form, $view_field)
  {
    $form['render_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Render Key'),
      '#description' => t('Pick the key under which the data is stored, otherwise leave blank to render everything. Will print_r() arrays.'),
      '#default_value' => $view_field->options['render_key'],
    );
    $form['render_array'] = array(
      '#type' => 'select',
      '#title' => t('Render arrays'),
      '#description' => t('Select the type of rendering you would like to do if the render key points to an array'),
      '#options' => array(
        'key' => 'print the key and ignore the array',
        'print_r' => 'print_r() the array',
        'list_keys' => 'list the keys in the current array',
        'list_keys_nested' => 'list keys and subkeys for a nested array',
      ),
      '#default_value' => $view_field->options['render_array'],
    );
    $form['render_array_max'] = array(
      '#title' => t('Limit key for numeric arrays'),
      '#description' => t('The limiting numeric key when generating a list from a non-associateive array. Usually returns a number of items equal to the number entered. Enter 0 to allow everything.'),
      '#type' => 'textfield',
      '#default_value' => isset($view_field->options['render_array_max']) ? $view_field->options['render_array_max'] : '',
      '#states' => array(
        'visible' => array(
          array(
            ':input[name="options[render_array]"]' => array('value' => 'list_keys'),
          ),
          array(
            ':input[name="options[render_array]"]' => array('value' => 'list_keys_nested'),
          ),
        ),
      ),
    );
    return $form;
  }

  public static function is_assoc(array $array)
  {
    return (array_values($array) !== $array);
  }

  /**
   * Helper function for DataViewerMarkup().
   */
  protected function generate_nested_key_list($base, $array, $max = 5) {
    $text = '';
    $keys = array_keys($array);
    foreach($keys as $key) {
      if (!is_numeric($key) || (is_numeric($key) && ($key < $max || !$max))) {
        if (!is_array($array[$key])) {
          $text .= $base . $key . PHP_EOL;
        }
        else {
          $text .= $base . $key . PHP_EOL;
          $text .= $this->generate_nested_key_list($base . $key . '.', $array[$key], $max);
        }
      }
    }
    return $text;
  }

  /**
   * @param $render_key
   * @param $render_method
   * @param $data_map
   * @return string
   */
  public function DataViewerMarkup($render_key, $render_method, $data_map, $render_max) {
    // Determine first if we have a multi-part key
    $key_exploded = explode('.', $render_key);
    if (is_array($key_exploded) && count($key_exploded) > 1) {
      $key_first = array_shift($key_exploded);
      $new_render_key = implode('.', $key_exploded);
      $map = isset($data_map[$key_first]) ? $data_map[$key_first] : '';
      return $this->DataViewerMarkup($new_render_key, $render_method, $map, $render_max);
    }
    // OK, we have a simple key case.
    else {
      $map = '';
      if ($render_key) {
        $map = isset($data_map[$render_key]) ? $data_map[$render_key] : '';
      }
      else {
        $map = $data_map;
      }
      if (!is_array($map)) {
        return (string) $map;
      }
      else {
        switch ($render_method) {
          case 'key':
            return $render_key;
            break;
          case 'print_r':
            $ret = print_r($map, TRUE);
            break;
          case 'list_keys':
            $ret = implode(PHP_EOL, array_keys($map));
            break;
          case 'list_keys_nested':
            $ret = $this->generate_nested_key_list('', $map, $render_max);
            break;
        }
        return '<pre>' . $ret . '</pre>';
      }
    }
  }
}

?>