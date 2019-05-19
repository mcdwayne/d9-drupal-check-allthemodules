<?php

namespace Drupal\theme_breakpoints_js;

use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Theme\ThemeInitialization;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * The ThemeBreakpointJs service for convenient loading of breakpoints.
 *
 * @package Drupal\theme_breakpoints_js
 */
class ThemeBreakpointsJs {

  /**
   * The breakpoint manager to get the breakpoints of a theme.
   *
   * @var \Drupal\breakpoint\BreakpointManager
   */
  protected $breakpointManager;

  /**
   * The theme manager to get the active theme.
   *
   * @var \Drupal\Core\Theme\ThemeManager
   */
  protected $themeManager;

  /**
   * Provides the theme initialization logic.
   *
   * Is used for getting the base themes.
   *
   * @var \Drupal\Core\Theme\ThemeInitialization
   */
  protected $themeInitialization;

  /**
   * A list of currently known active theme objects.
   *
   * @var \Drupal\Core\Theme\ActiveTheme[]
   */
  protected $activeThemes;

  /**
   * A list of loaded breakpoints, keyed by theme name.
   *
   * @var array
   */
  protected $breakpointsByTheme;

  /**
   * ThemeBreakpointsJs constructor.
   *
   * @param \Drupal\breakpoint\BreakpointManagerInterface $breakpoint_manager
   *   The breakpoint manager to get the breakpoints of a theme.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager to get the active theme.
   * @param \Drupal\Core\Theme\ThemeInitialization $theme_initialization
   *   Provides the theme initialization logic.
   */
  public function __construct(BreakpointManagerInterface $breakpoint_manager, ThemeManagerInterface $theme_manager, ThemeInitialization $theme_initialization) {
    $this->activeThemes = [];
    $this->breakpointsByTheme = [];
    $this->breakpointManager = $breakpoint_manager;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
  }

  /**
   * Gets the defined breakpoints for the active theme of the current route.
   *
   * @return \Drupal\breakpoint\BreakpointInterface[]
   *   The breakpoints.
   */
  public function getBreakpointsForActiveTheme() {
    $theme = $this->themeManager->getActiveTheme();
    $this->activeThemes[$theme->getName()] = $theme;
    return $this->getBreakpoints($theme->getName());
  }

  /**
   * Returns defined breakpoints for the provided theme.
   *
   * When the given theme does not have defined any breakpoints by itself,
   * the base theme's breakpoint definitions will be loaded instead (if any).
   *
   * @param string $theme_name
   *   The machine name of the theme.
   *
   * @return \Drupal\breakpoint\BreakpointInterface[]
   *   The breakpoints, keyed by machine name without theme prefix.
   */
  public function getBreakpoints($theme_name) {
    if (!isset($this->breakpointsByTheme[$theme_name])) {
      $theme = !empty($this->activeThemes[$theme_name]) ? $this->activeThemes[$theme_name]
        : $this->themeInitialization->getActiveThemeByName($theme_name);
      $this->activeThemes[$theme_name] = $theme;
      $base_themes = $theme->getBaseThemes();
      $theme_candidates = !empty($base_themes) ? array_keys($base_themes) : [];
      array_unshift($theme_candidates, $theme_name);

      $this->breakpointsByTheme[$theme_name] = [];
      foreach ($theme_candidates as $candidate_name) {
        if (($breakpoints = $this->breakpointManager->getBreakpointsByGroup($candidate_name)) && !empty($breakpoints)) {
          foreach ($breakpoints as $id => $breakpoint) {
            $machine_name = preg_replace('/^' . $candidate_name . '\./', '', $id);
            $this->breakpointsByTheme[$theme_name][$machine_name] = $breakpoint;
          }
          break;
        }
      }
    }
    return $this->breakpointsByTheme[$theme_name];
  }

}
