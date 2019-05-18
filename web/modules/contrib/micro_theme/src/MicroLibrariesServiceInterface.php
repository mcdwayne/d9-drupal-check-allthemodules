<?php

namespace Drupal\micro_theme;

/**
 * Interface LibrariesServiceInterface.
 */
interface MicroLibrariesServiceInterface {

  /**
   * Get all the libraries
   *
   * @return array
   */
  public function getAllLibraries();

  /**
   * Get all the libraries given a module.
   *
   * @param $module_name
   * @return array
   */
  public function getModuleLibraries($module_name);

  /**
   * Get all the libraries given a module.
   *
   * @param $module_name
   *   The module name.
   * @param $key
   *   The library key.
   * @return array
   */
  public function getModuleLibrary($module_name, $key);

  /**
   * Get all the libraries given a theme.
   *
   * @param $theme
   * @return array
   */
  public function getThemeLibraries($theme);

  /**
   * Get all the fonts availables.
   *
   * @return array
   *   The font names keyed by the library key.
   */
  public function getFonts();

  /**
   * Get the font name from the library key.
   *
   * @param string $key
   *   The library key.
   * @return string
   *   The font name to use in the css file.
   */
  public function getFont($key);

  /**
   * Get the color key from the YMl file.
   *
   * @param bool $sort
   *   Sort the array by the weight
   * @return array
   *   The color keys to use in the css file.
   */
  public function getColorsKey($sort = FALSE);

  /**
   * Get the default color from the YAML file.
   *
   * @return mixed
   */
  public function getDefaultColors();

}
