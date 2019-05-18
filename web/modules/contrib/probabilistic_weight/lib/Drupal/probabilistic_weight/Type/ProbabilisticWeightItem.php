<?php

/**
 * @file
 * Contains \Drupal\probabilistic_weight\Type\ProbabilisticWeightItem.
 */

namespace Drupal\probabilistic_weight\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'probabilistic_weight' entity field items.
 */
class ProbabilisticWeightItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see ProbabilisticWeightItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'float',
        'label' => t('Probabilistic weight'),
      );
    }
    return static::$propertyDefinitions;
  }
}
