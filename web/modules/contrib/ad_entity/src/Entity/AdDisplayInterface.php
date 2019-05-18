<?php

namespace Drupal\ad_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Theme\ActiveTheme;

/**
 * Provides an interface for defining Display configurations for Advertisement.
 */
interface AdDisplayInterface extends ConfigEntityInterface {

  /**
   * Get the Advertising entities to show as variants for the given theme.
   *
   * @param \Drupal\Core\Theme\ActiveTheme $theme
   *   The theme as ActiveTheme object.
   *
   * @return array
   *   A mapping list of variants, keyed by AdEntity id.
   */
  public function getVariantsForTheme(ActiveTheme $theme);

}
