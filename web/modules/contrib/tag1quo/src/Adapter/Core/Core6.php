<?php

namespace Drupal\tag1quo\Adapter\Core;

use Drupal\tag1quo\Adapter\Extension\Extension;

/**
 * Class Core6.
 *
 * @internal This class is subject to change.
 */
class Core6 extends Core {

  /**
   * {@inheritdoc}
   */
  protected $compatibility = 6;

  /**
   * {@inheritdoc}
   */
  protected $fallbackThemeDefault = 'garland';

  /**
   * {@inheritdoc}
   */
  public function absoluteUri($uri = '') {
    return $uri ? \url($uri, array('absolute' => TRUE)) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function convertElement(array $element = array()) {
    if (isset($element['#type'])) {
      switch ($element['#type']) {
        case 'details':
          $element['#type'] = 'fieldset';
          $open = isset($element['#open']) ? !!$element['#open'] : FALSE;
          $element['#collapsible'] = TRUE;
          $element['#collapsed'] = !$open;
          break;

        case 'table':
          unset($element["#type"]);
          $element['#theme'] = 'table';
          break;
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function elementInfo($type) {
    return \_element_info($type);
  }

  /**
   * {@inheritdoc}
   */
  public function extensionList() {
    // We send the entire system table to make it possible to properly match
    // all modules and themes with the proper upstream Drupal projects.
    $extensions = array();
    $result = db_query('SELECT * FROM {system}');
    while ($item = db_fetch_array($result)) {
      $extensions[$item['name']] = Extension::create($item['name'], (object) $item);
    }
    return $extensions;
  }

  public function publicPath() {
    return \file_directory_path();
  }

  /**
   * {@inheritdoc}
   */
  public function redirect($route_name, array $options = array(), $status = 302, array $route_parameters = array()) {
    $path = $this->routeToPath($route_name);
    $query = isset($options['query']) ? $options['query'] : NULL;
    $fragment = isset($options['fragment']) ? $options['fragment'] : NULL;
    \drupal_goto($path, $query, $fragment, $status);
  }

  /**
   * {@inheritdoc}
   */
  public function themeSetting($name, $default = NULL, $theme = NULL) {
    global $theme_key;

    // By default, if no theme is specified, the theme defaults to the "active"
    // theme. If the command is run from the CLI, via Drush, this will likely be
    // the "admin" theme. This isn't what is truly desired, so this should
    // default to the "default", front-facing, theme.
    if ($theme === NULL) {
      $theme = $this->defaultTheme();
    }

    // Unfortunately, in 6.x, theme_get_setting() doesn't allow for a
    // specific theme key to be passed. Instead, the global $theme_key
    // must be temporarily overridden to get the same desired effect.
    $original_theme_key = $theme_key;
    $theme_key = $theme;

    // Retrieve the absolute logo URL.
    $value = theme_get_setting($name);

    // Restore the original theme key.
    $theme_key = $original_theme_key;

    return $value !== NULL ? $value : $default;
  }

}
