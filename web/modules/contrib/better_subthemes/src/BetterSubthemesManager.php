<?php

namespace Drupal\better_subthemes;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Class BetterSubthemesManager.
 *
 * @package Drupal\better_subthemes
 */
class BetterSubthemesManager {

  /**
   * The active theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $activeTheme;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a new BetterSubthemesManager.
   *
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(ThemeInitializationInterface $theme_initialization, ThemeManagerInterface $theme_manager) {
    $this->themeInitialization = $theme_initialization;
    $this->themeManager = $theme_manager;
    $this->activeTheme = $this->themeManager->getActiveTheme();
  }

  /**
   * Get the source theme of the specified Better sub-themes feature type.
   *
   * @param string $type
   *   The Better sub-theme feature to check.
   * @param string|NULL $theme_name
   *   The name of the current theme.
   *
   * @return ActiveTheme
   *   Returns the source theme of the specified Better sub-themes feature type.
   */
  public function getSourceTheme($type, $theme_name = NULL) {
    $theme = $this->activeTheme;
    if (!is_null($theme_name)) {
      // Get initialized theme.
      /** @var \Drupal\Core\Theme\ActiveTheme $theme */
      $theme = $this->themeInitialization->initTheme($theme_name);
    }

    // Build list of themes.
    $themes = [$theme_name => $theme];
    $themes += $theme->getBaseThemes();

    // If we are inheriting the block layout, iterate over our base themes until
    // we find the source, then temporarily set it as the active theme.
    foreach ($themes as $theme) {
      if ($this->isSourceTheme($type, $theme)) {
        return $theme;
      }
    }

    return $theme;
  }

  /**
   * Check if the current theme is the source theme.
   *
   * @param string $type
   *   The Better sub-themes feature type to check.
   * @param ActiveTheme $theme
   *   The current theme to check.
   *
   * @return bool
   *   Returns TRUE if the current theme is the Better sub-themes feature type
   *   source, otherwise returns FALSE.
   */
  protected function isSourceTheme($type, ActiveTheme $theme) {
    // If this theme doesn't have any base themes, it's the source theme.
    if (empty($theme->getBaseThemes())) {
      return TRUE;
    }

    // Ensure we have the required extension data.
    $extension = $theme->getExtension();
    if (!isset($extension->info)) {
      return TRUE;
    }

    // If this theme doesn't implement 'better subthemes', it's the source
    // theme.
    if (!isset($extension->info['better subthemes'])) {
      return TRUE;
    }

    // If this theme doesn't implement the specified Better sub-themes feature
    // type, it's the source theme.
    if (!isset($extension->info['better subthemes'][$type]) || !$extension->info['better subthemes'][$type]) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Re-map the block lahyout assignments.
   *
   * @param array $assignments
   *   The block layout assignments.
   *
   * @return array
   *   The block layout assignments.
   */
  public function remapBlockLayout($assignments) {
    $extension = $this->activeTheme->getExtension();
    if (isset($extension->info) && isset($extension->info['better subthemes']['block layout remap']) && is_array($extension->info['better subthemes']['block layout remap'])) {
      foreach ($extension->info['better subthemes']['block layout remap'] as $source => $destination) {
        if (isset($assignments[$source])) {
          if (!isset($assignments[$destination])) {
            $assignments[$destination] = [];
          }

          $assignments[$destination] += $assignments[$source];
          unset($assignments[$source]);
        }
      }
    }
    return $assignments;
  }

}
