<?php

namespace Drupal\commerce_ups;

use Ups\Entity\UnitOfMeasurement;

/**
 * Constructs a UPS Entity.
 *
 * @package Drupal\commerce_ups
 */
abstract class UPSEntity {

  /**
   * Sets the unit of measurement for a UPS Entity.
   *
   * @param string $code
   *   The unit of measurement string.
   *
   * @return \Ups\Entity\UnitOfMeasurement
   *   The unit or measurement object for an API request.
   */
  public function setUnitOfMeasurement($code) {
    $ups_unit = new UnitOfMeasurement();
    $ups_unit->setCode($code);

    return $ups_unit;
  }

  /**
   * Convert commerce UOM to UPS API UOM.
   *
   * @param string $unit
   *   The string value provided by the Physical module.
   *
   * @return string
   *   The string value expected by UPS API.
   */
  public function getUnitOfMeasure($unit) {
    switch ($unit) {
      case 'lb':
        return UnitOfMeasurement::PROD_POUNDS;

      case 'kg':
        return UnitOfMeasurement::PROD_KILOGRAMS;

      case 'in':
        return UnitOfMeasurement::UOM_IN;

      case 'cm':
        return UnitOfMeasurement::UOM_CM;
    }

    return $unit;
  }

}
