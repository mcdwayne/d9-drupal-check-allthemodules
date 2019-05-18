<?php

namespace Drupal\plus;

use Drupal\plus\Utility\Unicode;

/**
 * The primary helper class for the Object Oriented Base Theme.
 *
 * Provides many helper methods.
 *
 * @ingroup utility
 */
class Plus {

  /**
   * The Theme Plugin Manager service.
   *
   * @var \Drupal\plus\ThemePluginManager
   */
  protected static $themePluginManager;

  /**
   * Adds a callback to an array.
   *
   * @param array $callbacks
   *   An array of callbacks to add the callback to, passed by reference.
   * @param array|string $callback
   *   The callback to add.
   * @param array|string $replace
   *   If specified, the callback will instead replace the specified value
   *   instead of being appended to the $callbacks array.
   * @param string $placement
   *   Flag that determines how to add the callback to the array.
   *
   * @return bool
   *   TRUE if the callback was added, FALSE if $replace was specified but its
   *   callback could be found in the list of callbacks.
   *
   * @throws \InvalidArgumentException
   *   If the $placement is not a valid type.
   */
  public static function addCallback(array &$callbacks, $callback, $replace = NULL, $placement = 'append') {
    // Replace a callback.
    if ($replace) {
      // Iterate through the callbacks.
      foreach ($callbacks as $key => $value) {
        // Convert each callback and match the string values.
        if (Unicode::convertCallback($value) === Unicode::convertCallback($replace)) {
          $callbacks[$key] = $callback;
          return TRUE;
        }
      }
      // No match found and action shouldn't append or prepend.
      if ($placement !== 'replace_append' || $placement !== 'replace_prepend') {
        return FALSE;
      }
    }

    // Append or prepend the callback.
    switch ($placement) {
      case 'append':
      case 'replace_append':
        $callbacks[] = $callback;
        return TRUE;

      case 'prepend':
      case 'replace_prepend':
        array_unshift($callbacks, $callback);
        return TRUE;

      default:
        throw new \InvalidArgumentException(t('Unknown placement type: @placement', ['@placement' => $placement]));
    }
  }

  /**
   * Retrieves an PlusTheme plugin instance for the active theme.
   *
   * @return \Drupal\plus\Plugin\Theme\ThemeInterface
   *   A theme object.
   */
  public static function getActiveTheme() {
    return static::getThemePluginManager()->getActiveTheme();
  }

  /**
   * Retrieves an Theme plugin instance for a specific theme.
   *
   * @param string|\Drupal\plus\Plugin\Theme\ThemeInterface|\Drupal\Core\Extension\Extension $theme
   *   The name of a theme, Theme plugin instance or an Extension object. If
   *   not provided, the active theme will be used instead.
   *
   * @return \Drupal\plus\Plugin\Theme\ThemeInterface
   *   A theme object.
   */
  public static function getTheme($theme = NULL) {
    return static::getThemePluginManager()->getTheme($theme);
  }

  /**
   * Retrieves Theme plugin instances for a specified themes.
   *
   * @param string[]|\Drupal\plus\Plugin\Theme\ThemeInterface[]|\Drupal\Core\Extension\Extension[] $themes
   *   An array of theme names, Theme plugin instances or an Extension objects.
   *   If omitted entirely, then all installed themes will be loaded.
   * @param bool $filter
   *   Filters out themes that are not Plus based.
   *
   * @return \Drupal\plus\Plugin\Theme\ThemeInterface[]
   *   An array of theme object, keyed by the theme machine name.
   */
  public static function getThemes(array $themes = NULL, $filter = TRUE) {
    return static::getThemePluginManager()->getThemes($themes, $filter);
  }

  /**
   * Retrieves the Theme Plugin Manager service.
   *
   * @return \Drupal\plus\ThemePluginManager
   *   The Theme Plugin Manager service.
   */
  public static function getThemePluginManager() {
    if (!isset(static::$themePluginManager)) {
      static::$themePluginManager = \Drupal::service('plugin.manager.theme');
    }
    return static::$themePluginManager;
  }

  /**
   * Returns the default http client.
   *
   * @return \Drupal\plus\Http\Client|object
   *   A guzzle HTTP client instance.
   */
  public static function httpClient() {
    return \Drupal::getContainer()->get('http_client');
  }

}
