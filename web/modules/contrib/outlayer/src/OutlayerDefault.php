<?php

namespace Drupal\outlayer;

use Drupal\gridstack\GridStackDefault;

/**
 * Defines shared plugin default settings.
 */
class OutlayerDefault extends GridStackDefault {

  /**
   * {@inheritdoc}
   */
  public static function baseSettings() {
    return [
      'grid_custom' => '',
      'outlayer'    => 'default',
    ] + parent::baseSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function viewsSettings() {
    return [
      'filter' => '',
      'sorter' => '',
    ] + self::baseSettings() + parent::viewsSettings() + parent::extendedSettings();
  }

  /**
   * Returns Views specific settings.
   */
  public static function viewsFilterSettings() {
    return [
      'filters'       => '',
      'filter_reset'  => '',
      'outlayer'      => '',
      'search_reset'  => '',
      'searchable'    => '',
    ];
  }

  /**
   * Returns Views specific settings.
   */
  public static function viewsSorterSettings() {
    return [
      'sorters'    => [],
      'outlayer'   => '',
      'sort_by'    => 'original-order',
      'sort_title' => 'Sort by',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function extendedSettings() {
    return self::viewsSettings()
      + self::viewsFilterSettings()
      + self::viewsSorterSettings()
      + parent::extendedSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function htmlSettings() {
    return [
      'columnWidthSizer' => FALSE,
      'gutterSizer'      => FALSE,
      'rowHeightSizer'   => FALSE,
    ] + parent::htmlSettings();
  }

  /**
   * Returns default layouts which don't require extra JS to download.
   */
  public static function inclusiveLayouts() {
    return [
      'masonry'  => 'masonry',
      'fitRows'  => 'fitRows',
      'vertical' => 'vertical',
    ];
  }

  /**
   * Returns layouts which require JS to download: layout:filename.
   *
   * The folder name is prefixed with `isotope-`.
   */
  public static function extraLayouts() {
    return [
      'cellsByColumn'     => 'cells-by-column',
      'cellsByRow'        => 'cells-by-row',
      'fitColumns'        => 'fit-columns',
      'horiz'             => 'horizontal',
      'masonryHorizontal' => 'masonry-horizontal',
      'packery'           => 'packery',
    ];
  }

  /**
   * Returns additional libraries that have been detected, or an empty array.
   */
  public static function checkExtraLibraries() {
    $libraries = [];
    foreach (self::extraLayouts() as $name => $id) {
      $library = 'libraries/isotope-' . $id;
      if (function_exists('libraries_get_path')) {
        $library = libraries_get_path('isotope-' . $id);
      }

      $filename = $name == 'packery' ? $id . '-mode' : $id;
      $ext = is_file($library . '/' . $filename . '.min.js') ? 'min.js' : 'js';
      if (is_file($library . '/' . $filename . '.' . $ext)) {
        $libraries[$name] = $name;
      }
    }
    return $libraries;
  }

}
