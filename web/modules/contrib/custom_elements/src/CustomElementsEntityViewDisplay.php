<?php

namespace Drupal\custom_elements;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Customized entity display to take over entity rendering.
 */
class CustomElementsEntityViewDisplay extends EntityViewDisplay {

  use CustomElementsEntityViewDisplayTrait;

  /**
   * Returns whether the entity is rendered via custom elements.
   *
   * @return bool
   */
  public function isCustomElementsEnabled() {
    return (bool) $this->getThirdPartySetting('custom_elements', 'enabled', 0);
  }

  /**
   * {@inheritDoc}
   */
  public function buildMultiple(array $entities) {
    if (!$this->isCustomElementsEnabled()) {
      return parent::buildMultiple($entities);
    }
    $build_list = [];
    $this->buildMultipleViaCustomElements($build_list, $entities);
    return $build_list;
  }

}
