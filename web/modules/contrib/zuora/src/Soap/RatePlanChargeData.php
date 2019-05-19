<?php

namespace Drupal\zuora\Soap;

/**
 * Class RatePlanChargeData
 *
 * @property array RatePlanCharge
 */
class RatePlanChargeData extends zObject {
  protected $zNamespace = 'http://api.zuora.com/';

  protected $zType = 'RatePlanChargeData';

  public function __construct(array $values) {
    parent::__construct($values);

    if (!empty($this->RatePlanChargeTier)) {
      /** @var \Drupal\zuora\Soap\RatePlanChargeTier $charge */
      foreach ($this->RatePlanChargeTier as $key => $charge) {
        $this->RatePlanChargeTier[$key] = $charge->getSoapVar();
      }
    }
  }
}
