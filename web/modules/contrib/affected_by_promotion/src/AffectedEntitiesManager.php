<?php

namespace Drupal\affected_by_promotion;

use Drupal\commerce_promotion\Entity\PromotionInterface;

/**
 * AffectedEntitiesManager service.
 */
class AffectedEntitiesManager {

  /**
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   * @param $entity_type_id
   *
   * @return bool|\Drupal\Core\Database\Query\Query
   */
  public function getAffectedEntitiesQuery(PromotionInterface $promotion, $entity_type_id) {
    $offer = $promotion->getOffer();
    if (!$offer instanceof SupportsAffectedEntitiesQueryInterface) {
      return FALSE;
    }
    return $offer->getAffectedEntitiesQuery($entity_type_id);
  }

}
