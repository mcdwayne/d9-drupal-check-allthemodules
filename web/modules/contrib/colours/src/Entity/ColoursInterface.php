<?php

namespace Drupal\colours\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Colours entities.
 */
interface ColoursInterface extends ConfigEntityInterface {
  
  /**
   * Checks if there is at least one colourset mapping defined.
   *
   * @return bool
   *   Whether the entity has any colours mappings.
   */
  public function hasColoursetMappings();

  /**
   * Returns the mappings of colour_css_selector to a colour.
   *
   * @return array[]
   *   The colourset mappings. Keyed by colour_css_selector.
   */
  public function getKeyedColoursetMappings();
  
  /**
   * Returns the colourset mappings for the responsive image style.
   *
   * @return array[]
   *   An array of colourset mappings. Each colourset mapping array
   *   contains the following keys:
   *   - colour_css_selector
   *   - colour_title
   *   - colour_background
   *   - colour_foreground
   */
  public function getColoursetMappings();

}
