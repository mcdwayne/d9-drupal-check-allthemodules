<?php

/**
 * Contains \Drupal\design_test\Plugin\Menu\LocalTask\ThemeDerivativeLocalTask
 */

namespace Drupal\design_test\Plugin\Menu\LocalTask;

use Drupal\Component\Plugin\Derivative\DerivativeBase;

class ThemeDerivativeLocalTask extends DerivativeBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $weight = 0;
    // @todo moduleHandler exists, but themeHandler does not?
    #$themes = \Drupal::themeHandler()->listInfo();
    $themes = list_themes();
    foreach ($themes as $name => $theme) {
      $this->derivatives[$name] = $base_plugin_definition;
      $this->derivatives[$name]['title'] = $theme->info['name'];
      $this->derivatives[$name]['route_parameters'] = array(
        'theme' => $name
      );
      $this->derivatives[$name]['weight'] = $weight++;
    }
    return $this->derivatives;
  }

}
