<?php

namespace Drupal\shopify\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\BasicStringFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the Shopify price formatter.
 *
 * @FieldFormatter(
 *   id = "shopify_variant_title",
 *   label = @Translation("Variant title"),
 *   field_types = {
 *     "string_long",
 *     "string",
 *   }
 * )
 */
class ShopifyVariantTitleFormatter extends BasicStringFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items);

    // Go up the chain from the Shopify product variant to get the parent
    // product title, and build the full product name with variation.
    $variant_title = ($elements[0]['#context']['value'] == 'Default Title') ? '' : ' - ' . $elements[0]['#context']['value'];
    $parent_title = $items->getParent()->getValue('entity')->getProduct()->title->value;
    $elements[0]['#context']['value'] = $this->t($parent_title . $variant_title);

    return $elements;
  }

}
