<?php

namespace Drupal\commerce_avatax;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

final class Avatax {

  /**
   * Gets the list of Tax exemption types.
   *
   * @return array
   *   The exemption types, keyed by code.
   */
  public static function getExemptionTypes() {
    return [
      'A' => new TranslatableMarkup('Federal government (United States)'),
      'B' => new TranslatableMarkup('State government (United States)'),
      'C' => new TranslatableMarkup('Tribe / Status Indian / Indian Band'),
      'D' => new TranslatableMarkup('Foreign diplomat'),
      'E' => new TranslatableMarkup('Charitable or benevolent org'),
      'F' => new TranslatableMarkup('Religious or educational org'),
      'G' => new TranslatableMarkup('Resale'),
      'H' => new TranslatableMarkup('Commercial agricultural production'),
      'I' => new TranslatableMarkup('Industrial production / manufacturer'),
      'J' => new TranslatableMarkup('Direct pay permit (United States)'),
      'K' => new TranslatableMarkup('Direct mail (United States)'),
      'L' => new TranslatableMarkup('Other'),
      'N' => new TranslatableMarkup('Local government (United States)'),
      'P' => new TranslatableMarkup('Commercial aquaculture (Canada)'),
      'Q' => new TranslatableMarkup('Commercial Fishery (Canada)'),
      'R' => new TranslatableMarkup('Non-resident (Canada)'),
    ];
  }

  /**
   * Check if the given order has Avatax adjustments.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if the order has Avatax adjustments, FALSE if not.
   */
  public static function hasAvataxAdjustments(OrderInterface $order) {
    /** @var \Drupal\commerce_order\Adjustment[] $avatax_adjustments */
    $avatax_adjustments = array_filter($order->collectAdjustments(), function (Adjustment $adjustment) use ($order) {
      return $adjustment->getType() == 'tax'
        && strpos($adjustment->getSourceId(), 'avatax|') !== FALSE;
    });
    return !empty($avatax_adjustments);
  }

}
