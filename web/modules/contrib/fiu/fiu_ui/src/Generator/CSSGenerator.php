<?php

namespace Drupal\fiu_ui\Generator;

class CSSGenerator {

  public static function generate() {
    $css_content = '';
    $name = 'general';
    $configs = \Drupal::config('fiu_ui.settings')->get();
    $path = drupal_get_path('module', 'fiu_ui');
    $css_file = $path . '/css/templates/' . $name . '.ccss';
    $css_content .= file_get_contents($css_file);
    foreach ($configs as $key => $variable) {
      $css_content = str_replace('%' . $key . '%', $variable, $css_content);
    }

    $dir = 'public://tmp/fiu';
    if (!file_prepare_directory($dir)) {
      drupal_mkdir($dir, NULL, TRUE);
    }
    $destination = $dir . '/' . $name . '.css';
    // Save css data.
    if (file_exists($destination)) {
      $param = FILE_EXISTS_REPLACE;
    }
    else {
      $param = FILE_EXISTS_RENAME;
    }
    file_unmanaged_save_data($css_content, $destination, $param);
  }
}
