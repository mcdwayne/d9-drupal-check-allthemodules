<?php

namespace Drupal\address_dawa\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\address_dawa\AddressDawaItemInterface;

/**
 * Plugin implementation of the 'address_dawa' formatter.
 *
 * @FieldFormatter(
 *   id = "address_dawa",
 *   label = @Translation("Address DAWA"),
 *   field_types = {
 *     "address_dawa",
 *   },
 * )
 */
class AddressDawaFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\address_dawa\AddressDawaItemInterface $item */
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#prefix' => '<p class="dawa-address" translate="no">',
        '#suffix' => '</p>',
      ];
      $elements[$delta] += $this->viewElement($item);

    }

    return $elements;
  }

  /**
   * Builds a renderable array for a single dawa address item.
   *
   * @param \Drupal\address_dawa\AddressDawaItemInterface $item
   *   The address.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement(AddressDawaItemInterface $item) {
    $value = [
      $item->getType(),
      $item->getId(),
      $item->getTextValue(),
      $item->getLat() . ', ' . $item->getLng(),
    ];
    $element = [
      '#type' => 'markup',
      '#markup' => implode('<br>', $value),
    ];
    return $element;
  }

}
