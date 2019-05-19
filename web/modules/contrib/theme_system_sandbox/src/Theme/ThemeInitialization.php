<?php
/**
 * @file
 * Contains \Drupal\theme_system_sandbox\Theme\ThemeInitialization.
 */

namespace Drupal\theme_system_sandbox\Theme;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Theme\ThemeInitialization as DefaultThemeInitialization;

class ThemeInitialization extends DefaultThemeInitialization {

  /**
   * {@inheritdoc}
   */
  public function getActiveTheme(Extension $theme, array $base_themes = []) {
    // @todo to remove this we need to change something in core.
    if (method_exists($this, 'prepareActiveTheme')) {
      $values = $this->prepareActiveTheme($theme, $base_themes);

      if (!empty($theme->info['component-overrides'])) {
        $values['component_overrides'] = $theme->info['component-overrides'];
      }

      return new ActiveTheme($values);
    }

    return parent::getActiveTheme($theme, $base_themes);
  }

}
