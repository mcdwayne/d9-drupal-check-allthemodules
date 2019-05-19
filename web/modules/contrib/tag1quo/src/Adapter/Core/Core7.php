<?php

namespace Drupal\tag1quo\Adapter\Core;

use Drupal\tag1quo\Adapter\Extension\Extension;

/**
 * Class Core7.
 *
 * @internal This class is subject to change.
 */
class Core7 extends Core {

  /**
   * {@inheritdoc}
   */
  protected $compatibility = 7;

  /**
   * {@inheritdoc}
   */
  protected $fallbackThemeDefault = 'bartik';

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
    return \element_info($type);
  }

  /**
   * {@inheritdoc}
   */
  public function extensionList() {
    // We send the entire system table to make it possible to properly match
    // all modules and themes with the proper upstream Drupal projects.
    $extensions = array();
    $result = \db_query('SELECT * FROM {system}');
    while ($item = $result->fetchObject()) {
      $extensions[$item->name] = Extension::create($item->name, $item);
    }
    return $extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function formatPlural($count, $singular, $plural, array $args = [], array $options = []) {
    return \format_plural($count, $singular, $plural, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function &getNestedValue(array &$array, array $parents, &$key_exists = NULL) {
    return \drupal_array_get_nested_value($array, $parents, $key_exists);
  }

  /**
   * {@inheritdoc}
   */
  public function mergeDeep($_) {
    $args = func_get_args();
    return \drupal_array_merge_deep_array($args);
  }

  /**
   * {@inheritdoc}
   */
  public function publicPath() {
    return $this->settings()->get('file_public_path', \conf_path() . '/files');
  }

  /**
   * {@inheritdoc}
   */
  public function t($string, array $args = array(), array $options = array()) {
    return \t($string, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function redirect($route_name, array $options = array(), $status = 302, array $route_parameters = array()) {
    $path = $this->routeToPath($route_name);
    \drupal_goto($path, $options, $status);
  }

  /**
   * {@inheritdoc}
   */
  public function themeSetting($name, $default = NULL, $theme = NULL) {
    // By default, if no theme is specified, the theme defaults to the "active"
    // theme. If the command is run from the CLI, via Drush, this will likely be
    // the "admin" theme. This isn't what is truly desired, so this should
    // default to the "default", front-facing, theme.
    if ($theme === NULL) {
      $theme = $this->defaultTheme();
    }
    $value = theme_get_setting($name, $theme);
    return $value !== NULL ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setNestedValue(array &$array, array $parents, $value, $force = FALSE) {
    \drupal_array_set_nested_value($array, $parents, $value, $force);
    return $this;
  }

}
