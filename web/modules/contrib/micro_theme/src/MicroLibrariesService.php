<?php

namespace Drupal\micro_theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class MicroLibrariesService.
 */
class MicroLibrariesService implements MicroLibrariesServiceInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\Asset\LibraryDiscoveryInterface definition.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Drupal\Core\Extension\ThemeHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new LibrariesService object.
   */
  public function __construct(ModuleHandlerInterface $module_handler, LibraryDiscoveryInterface $library_discovery, ThemeHandlerInterface $theme_handler) {
    $this->moduleHandler = $module_handler;
    $this->libraryDiscovery = $library_discovery;
    $this->themeHandler = $theme_handler;
  }

  /**
   * Get all the libraries
   *
   * @return array
   */
  public function getAllLibraries() {
    $libraries = [];
    $modules = $this->moduleHandler->getModuleList();
    $themes = $this->themeHandler->rebuildThemeData();
    $extensions = array_merge($modules, $themes);
    $root = \Drupal::root();
    /** @var \Drupal\Core\Extension\Extension $extension */
    foreach ($extensions as $extension_name => $extension) {
      $library_file = $extension->getPath() . '/' . $extension_name . '.libraries.yml';
      if (is_file($root . '/' . $library_file)) {
        $libraries[$extension_name] = $this->libraryDiscovery->getLibrariesByExtension($extension_name);
      }
    }
    return $libraries;
  }

  /**
   * Get all the libraries given a module.
   *
   * @param $module_name
   * @return array
   */
  public function getModuleLibraries($module_name) {
    $libraries = [];
    $modules = $this->moduleHandler->getModuleList();
    $root = \Drupal::root();

    if (isset($modules[$module_name])) {
      /** @var \Drupal\Core\Extension\Extension $extension */
      $extension = $modules[$module_name];
      $library_file = $extension->getPath() . '/' . $module_name . '.libraries.yml';
      if (is_file($root . '/' . $library_file)) {
        $libraries = $this->libraryDiscovery->getLibrariesByExtension($module_name);
      }
    }
    return $libraries;
  }

  /**
   * Get all the libraries given a module.
   *
   * @param $module_name
   *   The module name.
   * @param $key
   *   The library key.
   * @return array
   */
  public function getModuleLibrary($module_name, $key) {
    $libraries = $this->getModuleLibraries($module_name);
    return isset($libraries[$key]) ? $libraries[$key] : [];
  }

  /**
   * Get all the libraries given a theme.
   *
   * @param $theme
   * @return array
   */
  public function getThemeLibraries($theme) {
    $libraries = [];
    $themes = $this->themeHandler->rebuildThemeData();
    $root = \Drupal::root();

    if (isset($themes[$theme])) {
      /** @var \Drupal\Core\Extension\Extension $extension */
      $extension = $themes[$theme];
      $library_file = $extension->getPath() . '/' . $theme . '.libraries.yml';
      if (is_file($root . '/' . $library_file)) {
        $libraries = $this->libraryDiscovery->getLibrariesByExtension($theme);
      }
    }
    return $libraries;
  }

  /**
   * Get all the fonts available.
   *
   * @return array
   *   The font names keyed by the library key.
   */
  public function getFonts() {
    $options = [];
    $fonts = $this->getModuleLibraries('micro_theme');
    foreach ($fonts as $key => $font) {
      if ($key == 'preview' ||
          $key == 'jquery_minicolors' ||
          $key == 'form' ||
          $key == 'global') {
        continue;
      }
      $options[$key] = isset($font['name_css']) ? $font['name_css'] : $key;
    }

    asort($options);
    $this->moduleHandler->alter('micro_theme_fonts', $options);
    return $options;
  }

  /**
   * Get the font name from the library key.
   *
   * @param string $key
   *   The library key.
   * @return string
   *   The font name to use in the css file.
   */
  public function getFont($key) {
    $fonts = $this->getFonts();
    return isset($fonts[$key]) ? $fonts[$key]: '';
  }

  /**
   * Get the color key from the YMl file.
   *
   * @param bool $sort
   *   Sort the array by the weight
   * @return array
   *   The color keys to use in the css file.
   */
  public function getColorsKey($sort = FALSE) {
    $colors = $this->getDefaultColors();
    if ($sort) {
      uasort($colors, function($a, $b) {
        return $a['weight'] - $b['weight'];
      });
    }
    return array_keys($colors);
  }

  /**
   * Get the default color from the YAML file.
   *
   * @return mixed
   */
  public function getDefaultColors() {
    $yaml_default_color_path = \Drupal::root() . '/' . drupal_get_path('module', 'micro_theme') . '/micro_theme.default_color.yml';
    $file_content = file_get_contents($yaml_default_color_path);
    $yaml_default_color = Yaml::decode($file_content);
    $this->moduleHandler->alter('micro_theme_default_color', $yaml_default_color);
    return $yaml_default_color;
  }

}
