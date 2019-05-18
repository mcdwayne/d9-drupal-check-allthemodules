<?php

namespace Drupal\plus\Core\Extension;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ThemeHandler as CoreThemeHandler;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\plus\Plugin\Theme\ThemeInterface;

/**
 * {@inheritdoc}
 */
class ThemeHandler extends CoreThemeHandler {

  /**
   * A cached list of each theme's ancestry, keyed by machine name.
   *
   * @var array
   */
  protected $ancestry = [];

  /**
   * Retrieves a list of the full ancestry of a theme.
   *
   * @param string $theme
   *   The theme name to retrieve ancestry for. If not provided, the ancestry
   *   for the active theme is returned.
   * @param bool $reverse
   *   Whether or not to return the array of themes in reverse order, where the
   *   provided $theme is the first item in the list.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An associative array of Extension objects, keyed by machine name.
   */
  public function getAncestry($theme = NULL, $reverse = FALSE) {
    $name = $this->normalizeThemeName($theme);
    if (!isset($this->ancestry[$name])) {
      $themes = $this->listInfo();
      $this->ancestry[$name] = $this->getBaseThemes($themes, $name);
      $this->ancestry[$name][$name] = $themes[$name];
    }
    return array_keys($reverse ? array_reverse($this->ancestry[$name]) : $this->ancestry[$name]);
  }

  /**
   * Helper for normalizing an argument into the machine name of a theme.
   *
   * @param mixed $theme
   *   An object or string name of a theme.
   *
   * @return string
   *   The machine name of the theme.
   */
  public function normalizeThemeName($theme = NULL) {
    // Use active theme if no theme name or object was provided.
    if (!isset($theme)) {
      // Due to recursion, the Theme Manager service cannot be injected.
      $name = \Drupal::service('theme.manager')->getActiveTheme()->getName();
    }
    elseif ($theme instanceof ThemeInterface || $theme instanceof Extension || $theme instanceof ActiveTheme) {
      $name = $theme->getName();
    }
    else {
      $name = (string) $theme;
    }
    return $name;
  }

}
