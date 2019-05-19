<?php

namespace Drupal\twig_typography\TwigExtension;

use PHP_Typography\Settings;
use PHP_Typography\PHP_Typography;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides a typography twig filter.
 *
 * @package Drupal\twig_typography\TwigExtension
 */
class Typography extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('typography', [$this, 'applyTypography'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'typography.twig_extension';
  }

  /**
   * Runs the PHP-Typography.
   *
   * @param string $string
   *   The string of text to apply the filter to.
   * @param array $arguments
   *   An optional array containing the settings for the typography library.
   *   This should be set as a hash (key value pair) in twig template.
   * @param bool $use_defaults
   *   - TRUE: a sane set of defaults are loaded.
   *   - FALSE: settings will need to be passed in and no defaults will
   *     be applied.
   *
   * @return string
   *   A processed and filtered string to return to the template.
   *
   * @throws \Exception
   *   An exception is thrown if a string is not passed.
   */
  public static function applyTypography($string, array $arguments = [], $use_defaults = TRUE) {
    if (is_array($string)) {
      throw new \Exception(t('The typography twig filter can only operate on strings or rendered markup but an (render) array was passed.'));
    }
    $settings = new Settings($use_defaults);
    // Load the defaults from the theme and merge them with any
    // supplied arguments from the calling function in the template.
    $arguments = array_merge(self::getDefaults(), $arguments);
    // Process the arguments and add them to the settings object.
    foreach ($arguments as $setting => $value) {
      $settings->{$setting}($value);
    }
    $typo = new PHP_Typography();

    // Process the string with any provided arguments (and/or defaults) and
    // return it.
    $string = $typo->process($string, $settings);
    return $string;
  }

  /**
   * Gets defaults from a YAML file if it exists in the active theme directory.
   *
   * @return array
   *   A set of defaults loaded from a YAML file if found.
   */
  private static function getDefaults() {
    $defaults = [];
    $theme_name = static::getThemeName();
    $file_path = static::getFilePath($theme_name);
    if (file_exists($file_path)) {
      $defaults = Yaml::parse(file_get_contents($file_path));
    }
    return $defaults;
  }

  /**
   * Gets the active theme name.
   */
  public static function getThemeName() {
    return \Drupal::theme()->getActiveTheme()->getName();
  }

  /**
   * Returns a possible file path for a given theme.
   *
   * @param string $theme_name
   *   The theme name to find the file path for.
   *
   * @return string
   *   The file path to the typography_defaults.yml file.
   */
  public static function getFilePath($theme_name) {
    return drupal_get_path('theme', $theme_name) . '/typography_defaults.yml';
  }

}
