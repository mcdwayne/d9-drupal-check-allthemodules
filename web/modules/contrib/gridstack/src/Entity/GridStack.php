<?php

namespace Drupal\gridstack\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\gridstack\GridStackDefault;

/**
 * Defines the GridStack configuration entity.
 *
 * @ConfigEntityType(
 *   id = "gridstack",
 *   label = @Translation("GridStack optionset"),
 *   list_path = "admin/structure/gridstack",
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "label",
 *     "status",
 *     "weight",
 *     "options",
 *     "json",
 *   }
 * )
 */
class GridStack extends GridStackBase implements GridStackInterface {

  /**
   * The supported $breakpoints.
   *
   * @var array
   */
  private static $activeBreakpoints = ['xs', 'sm', 'md', 'lg', 'xl'];

  /**
   * Returns the supported breakpoints.
   */
  public static function getConstantBreakpoints() {
    return self::$activeBreakpoints;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($merged = TRUE) {
    $default = self::load('default');
    $options = $merged ? array_merge($default->options, $this->options) : $this->options;
    return isset($options['settings']) ? $options['settings'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $values, $merged = TRUE) {
    $settings = isset($this->options['settings']) ? $this->options['settings'] : [];
    $this->options['settings'] = $merged ? array_merge($settings, $values) : $values;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name) {
    return (NULL !== $this->getOptions('settings', $name)) ? $this->getOptions('settings', $name) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($name, $value) {
    $this->options['settings'][$name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndBreakpointGrids($current = 'grids') {
    $build = [];
    foreach ($this->getBreakpoints() as $key => $breakpoint) {
      if (empty($breakpoint['grids'])) {
        continue;
      }

      $build[$key] = $breakpoint;
    }

    $keys = array_keys($build);
    $end = end($keys);

    return $this->getBreakpointGrids($end, $current);
  }

  /**
   * {@inheritdoc}
   */
  public function getNestedGridsByDelta($delta = 0) {
    $grids = $this->getEndBreakpointGrids('nested');
    $nested = isset($grids[$delta]) ? $grids[$delta] : [];
    $check = array_filter($nested);

    return empty($check) ? [] : $nested;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpoints($breakpoint = NULL) {
    $breakpoints = $this->getOption('breakpoints') ?: [];
    if ($breakpoint && isset($breakpoints[$breakpoint])) {
      return $breakpoints[$breakpoint];
    }
    return $breakpoints;
  }

  /**
   * Returns options.breakpoints.sm.[width, column, image_style, grids, nested].
   */
  public function getBreakpointGrids($breakpoint = 'lg', $current = 'grids') {
    $grids = !empty($this->getBreakpoints($breakpoint)) && isset($this->getBreakpoints($breakpoint)[$current]) ? $this->getBreakpoints($breakpoint)[$current] : '';
    $grids = Json::decode($grids);

    if ($grids) {
      return $current == 'grids' ? array_filter($grids) : $grids;
    }
    return [];
  }

  /**
   * Returns options.breakpoints.sm.[width, column, image_style, grids, nested].
   */
  public function getBreakpointGrid($breakpoint = 'lg', $index = -1, $property = '', $current = 'grids') {
    $grids = $this->getBreakpointGrids($breakpoint, $current);

    return isset($grids[$index]) && isset($grids[$index][$property]) ? $grids[$index][$property] : NULL;
  }

  /**
   * Converts gridstack breakpoint grids from stored JSON into array.
   */
  public function gridsJsonToArray(array &$settings = []) {
    $settings['breakpoints'] = array_filter($this->getBreakpoints());

    if (!empty($settings['breakpoints'])) {
      foreach ($settings['breakpoints'] as $key => $breakpoint) {
        if (!empty($breakpoint['grids']) && is_string($breakpoint['grids'])) {
          $settings['breakpoints'][$key]['grids'] = Json::decode($breakpoint['grids']);
        }

        if (!empty($breakpoint['nested']) && is_string($breakpoint['nested'])) {
          $settings['breakpoints'][$key]['nested'] = Json::decode($breakpoint['nested']);
        }
      }
    }
  }

  /**
   * Optimize grid widths to remove similar widths.
   */
  public function optimizeGridWidths(array $settings = [], $current = 'grids', $optimize = FALSE) {
    $breakpoints  = isset($settings['breakpoints']) ? $settings['breakpoints'] : [];
    $delta        = isset($settings['delta']) ? $settings['delta'] : 0;
    $nested_delta = isset($settings['nested_delta']) ? $settings['nested_delta'] : NULL;

    $unique = [];
    foreach (static::$activeBreakpoints as $id) {
      $item = isset($breakpoints[$id]) && isset($breakpoints[$id][$current][$delta]) ? $breakpoints[$id][$current][$delta] : '';
      if (empty($item)) {
        continue;
      }

      if ($current == 'grids') {
        if (!empty($item['width'])) {
          $unique[$id] = (int) $item['width'];
        }
      }
      elseif ($current == 'nested') {
        if (isset($item[$nested_delta]) && !empty($item[$nested_delta]['width'])) {
          $unique[$id] = (int) $item[$nested_delta]['width'];
        }
      }
    }

    $reversed = array_reverse(array_unique($unique));
    return $optimize ? array_reverse($reversed) : $unique;
  }

  /**
   * Returns JSON for options.breakpoints[xs|sm|md|lg|xl] keyed by indices.
   */
  public function getJsonSummaryBreakpoints($breakpoint = 'lg', $exclude_image_style = FALSE, $no_keys = TRUE) {
    $grids = $this->getBreakpointGrids($breakpoint);

    if ($grids && $no_keys) {
      $values = [];
      foreach ($grids as &$grid) {
        if (empty($grid)) {
          continue;
        }

        if ($exclude_image_style && isset($grid['image_style'])) {
          array_pop($grid);
        }
        $values[] = array_values($grid);
      }

      // Simplify and remove keys:
      // Original: [{"x":1,"y":0,"width":2,"height":8}.
      // Now: [[1,0,2,8].
      $grids = $values;
    }

    return $grids ? Json::encode($grids) : '';
  }

  /**
   * Returns the icon URI.
   */
  public function getIconUri() {
    $id = $this->id();
    $uri = file_build_uri('gridstack/' . $id . '.png');

    // The icon was updated, and stored at public://gridstack/ directory.
    if (is_file($uri)) {
      return $uri;
    }

    // The icon may be empty, or not, yet not always exists at public directory.
    $uri          = $this->getOption('icon');
    $dependencies = $this->getDependencies();
    $module       = isset($dependencies['module'][0]) && !empty($dependencies['module'][0]) ? $dependencies['module'][0] : '';

    // Support static icons at MODULE_NAME/images/OPTIONSET_ID.png as long as
    // the module dependencies are declared explicitly for the stored optionset.
    if (empty($uri) || !is_file($uri)) {
      // Reset to empty first.
      $uri = '';
      $handler = \Drupal::service('gridstack.manager')->getModuleHandler();

      if ($module && $handler->moduleExists($module)) {
        $icon_path = drupal_get_path('module', $module) . '/images/' . $id . '.png';

        if (is_file(DRUPAL_ROOT . '/' . $icon_path)) {
          $uri = base_path() . $icon_path;
        }
      }
    }

    return $uri;
  }

  /**
   * Returns the icon URL.
   */
  public function getIconUrl($absolute = FALSE) {
    $url = '';

    if ($uri = $this->getIconUri()) {
      $url = file_url_transform_relative(file_create_url($uri));

      if (!$absolute) {
        $url = ltrim($url, '/');
      }
    }

    return $url;
  }

  /**
   * Parses the given string attribute.
   */
  public function parseAttributes($string = '') {
    $attributes = [];
    // Given role|navigation,data-something|some value.
    $layout_attributes = explode(',', $string);
    foreach ($layout_attributes as $attribute) {
      $replaced_attribute = $attribute;

      // @nottodo: Token support.
      // No need to whitelist as this already requires admin priviledges.
      // With admin privileges, the site is already taken over before playing
      // around with attributes. However provides few basic sanitizations to
      // satisfy curious playful editors.
      if (strpos($attribute, '|') !== FALSE) {
        list($key, $value) = array_pad(array_map('trim', explode('|', $replaced_attribute, 2)), 2, NULL);
        $key = substr($key, 0, 2) === 'on' ? 'data-' . $key : $key;
        $attributes[$key] = Html::cleanCssIdentifier(strip_tags($value));
      }
    }

    return $attributes;
  }

  /**
   * Parses the given string classes.
   */
  public function parseClassAttributes(array &$attributes, $string = '') {
    $classes = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', explode(' ', $string));
    $attributes['class'] = empty($attributes['class']) ? array_unique($classes) : array_unique(array_merge($attributes['class'], $classes));
  }

  /**
   * Provides dynamic GridStack JS grid attributes.
   */
  public function jsBoxAttributes(array &$settings, $current = 'grids') {
    $attributes  = $this->regionBoxAttributes($settings, $current);
    $nameshort   = $settings['nameshort'];
    $breakpoints = isset($settings['breakpoints']) ? $settings['breakpoints'] : [];
    $keys        = $breakpoints ? array_keys($breakpoints) : [];
    $end_key     = $keys ? end($keys) : 'xl';
    $breakpoint  = isset($settings['breakpoint']) ? $settings['breakpoint'] : $end_key;
    $id          = isset($settings['delta']) ? $settings['delta'] : 0;
    $nid         = isset($settings['nested_delta']) ? $settings['nested_delta'] : NULL;
    $end_grids   = $this->getEndBreakpointGrids($current);
    $grids       = isset($breakpoints[$breakpoint]) && isset($breakpoints[$breakpoint][$current]) ? $breakpoints[$breakpoint][$current] : $end_grids;

    // Nested grids.
    if (isset($settings['nested_delta']) && $current == 'nested') {
      foreach (['x', 'y', 'width', 'height'] as $key) {
        if (!isset($grids[$id][$nid])) {
          continue;
        }

        $attributes['data-' . $nameshort . '-' . $key] = isset($grids[$id][$nid][$key]) ? (int) $grids[$id][$nid][$key] : 0;
      }
    }
    else {
      // The root element grids.
      foreach (['x', 'y', 'width', 'height'] as $key) {
        $attributes['data-' . $nameshort . '-' . $key] = isset($grids[$id][$key]) ? (int) $grids[$id][$key] : 0;
      }
    }

    return $attributes;
  }

  /**
   * Provides static Bootstrap/ Foundation CSS grid attributes.
   */
  public function cssBoxAttributes(array &$settings, $current = 'grids', $optimize = FALSE) {
    $framework  = $settings['framework'];
    $attributes = $this->regionBoxAttributes($settings, $current);
    $points     = GridStackDefault::breakpoints();

    // Bootstrap 4 uses flexbox with `col` class, and has `xl` breakpoint.
    if ($framework == 'bootstrap') {
      $attributes['class'][] = 'col';
    }
    elseif ($framework == 'foundation') {
      unset($points['xs'], $points['xl']);
    }

    $unique = $this->optimizeGridWidths($settings, $current, $optimize);
    foreach ($points as $point => $label) {
      if (!isset($unique[$point])) {
        continue;
      }

      $prefix = $suffix = '';
      if (strpos($framework, 'bootstrap') !== FALSE) {
        // Specific to XS: Bootstrap 3: col-xs-*, Bootstrap 4: col-*.
        $prefix = 'col-' . $point . '-';
        if ($framework == 'bootstrap' && $point == 'xs') {
          $prefix = 'col-';
        }
      }
      elseif ($framework == 'foundation') {
        $prefix = $label . '-';
        $suffix = ' columns';
      }

      $attributes['class'][] = $prefix . $unique[$point] . $suffix;
    }

    return $attributes;
  }

  /**
   * Provides both CSS grid and js-driven attributes configurable via UI.
   */
  public function regionBoxAttributes(array &$settings, $current = 'grids') {
    $id         = isset($settings['delta']) ? $settings['delta'] : 0;
    $nid        = isset($settings['nested_delta']) ? $settings['nested_delta'] : NULL;
    $regions    = isset($settings['regions']) ? $settings['regions'] : [];
    $rid        = $current == 'nested' ? 'gridstack_' . $id . '_' . $nid : 'gridstack_' . $id;
    $region     = empty($regions[$rid]) ? [] : $regions[$rid];
    $attributes = [];

    if ($region) {
      if (isset($region['attributes']) && !empty($region['attributes'])) {
        $attributes = $this->parseAttributes($region['attributes']);
        unset($settings['regions'][$rid]['attributes']);
      }

      if (!empty($region['wrapper_classes'])) {
        $this->parseClassAttributes($attributes, $region['wrapper_classes']);
        unset($settings['regions'][$rid]['wrapper_classes']);
      }
    }

    return $attributes;
  }

  /**
   * Returns the wrapper attributes.
   */
  public function prepareAttributes(array $settings, $ungridstack = FALSE) {
    $attributes = empty($settings['attributes']) ? [] : $this->parseAttributes($settings['attributes']);

    if ($ungridstack) {
      return $attributes;
    }

    // Adds wrapper classes for static grid Bootstrap/ Foundation, or js-driven.
    if (!empty($settings['wrapper_classes']) && is_string($settings['wrapper_classes'])) {
      $this->parseClassAttributes($attributes, $settings['wrapper_classes']);
    }
    if ($settings['use_js']) {
      // Adds attributes for js-driven layouts.
      // Gets options.breakpoints.sm.[width, column, image_style, grids], etc.
      $exclude_image_style = empty($settings['_admin']);
      $columns = $this->getJson('breakpoints');
      if ($responsives = array_filter($this->getBreakpoints())) {
        foreach (static::$activeBreakpoints as $breakpoint) {
          $responsive_grids = $this->getJsonSummaryBreakpoints($breakpoint, $exclude_image_style);
          $has_width = isset($responsives[$breakpoint]['width']) && $responsives[$breakpoint]['width'] > -1;
          if ($has_width && $responsive_grids) {
            $attributes['data-' . $breakpoint . '-width'] = $responsives[$breakpoint]['width'];
            $attributes['data-' . $breakpoint . '-grids'] = $responsive_grids;
          }
        }
      }

      // Add the required configuration as JSON object.
      $attributes['data-breakpoints'] = strpos($columns, '{"":12}') !== FALSE ? '' : $columns;
      $attributes['data-config'] = $this->getJson('settings');

      // Breakpoint related data-attributes helpers.
      if (!empty($settings['minWidth'])) {
        $attributes['data-min-width'] = (int) $settings['minWidth'];
      }
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRegions($clean = TRUE) {
    $grids   = $this->getEndBreakpointGrids();
    $regions = [];

    foreach ($grids as $delta => $grid) {
      $label_index = ($delta + 1);
      $label = 'GridStack  ' . $label_index;

      $regions['gridstack_' . $delta]['label'] = $label;

      // With nested grids, its container doesn't contain contents, but grids.
      $nested_grids = $this->getNestedGridsByDelta($delta);
      $is_nested = array_filter($nested_grids);

      if ($is_nested) {
        // Remove container since the actual contents are moved, if required.
        if ($clean) {
          unset($regions['gridstack_' . $delta]);
        }

        foreach ($nested_grids as $nested_delta => $nested_grid) {
          $label = 'GridStack  ' . $label_index . ':' . ($nested_delta + 1);

          $regions['gridstack_' . $delta . '_' . $nested_delta]['label'] = $label;
        }
      }
    }

    return $regions;
  }

}
