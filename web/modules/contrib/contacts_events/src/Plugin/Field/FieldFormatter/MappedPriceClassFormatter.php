<?php

namespace Drupal\contacts_events\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'mapped_price_class' formatter.
 *
 * @FieldFormatter(
 *   id = "mapped_price_class",
 *   label = @Translation("Mapped price class"),
 *   field_types = {
 *     "mapped_price_data"
 *   }
 * )
 */
class MappedPriceClassFormatter extends MappedPriceDataFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $order_item = $this->getOrderItem($items);
    $price_items = $this->priceCalculator->findPriceMap($order_item);
    $classes = $price_items->getClasses();

    foreach ($items as $delta => $item) {
      // See if we can show the class.
      if ($item->class) {
        foreach ($classes as $class) {
          if ($class->id() == $item->class) {
            $elements[$delta]['class']['#markup'] = $class->label();
            break;
          }
        }
      }
    }

    return $elements;
  }

}
