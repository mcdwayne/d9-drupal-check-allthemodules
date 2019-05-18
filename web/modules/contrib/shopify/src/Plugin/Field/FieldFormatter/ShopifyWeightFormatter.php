<?php

namespace Drupal\shopify\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\NumericFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the Shopify weight formatter.
 *
 * @FieldFormatter(
 *   id = "shopify_weight",
 *   label = @Translation("Shopify Weight"),
 *   field_types = {
 *     "decimal",
 *   }
 * )
 */
class ShopifyWeightFormatter extends NumericFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $entity = $item->getEntity();
      $output = $this->numberFormat($item->value, $entity->weight_unit->value);
      $elements[$delta] = ['#markup' => $output];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = 'Format: {{weight}}{{weight_unit}}';
    $summary[] = 'Preview: ' . $this->numberFormat(1234.1234567890, 'lb');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function numberFormat($number, $format = '') {
    $number = number_format($number, 2);
    return $number . $format;
  }

}
