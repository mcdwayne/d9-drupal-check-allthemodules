<?php

namespace Drupal\xero\Normalizer;

use Drupal\serialization\Normalizer\TypedDataNormalizer;
use Drupal\xero\Plugin\DataType\XeroItemList;
use Drupal\xero\TypedData\XeroTypeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Implements normalization of Xero data types wrapped in XeroListitem.
 *
 * Drupal's ListNormalizer is explicitly for field data and does not pass in
 * the plugin identifier as the context.
 */
class XeroListNormalizer extends TypedDataNormalizer implements NormalizerInterface {

  protected $supportedInterfaceOrClass = 'Drupal\xero\Plugin\DataType\XeroItemList';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array(), $top = TRUE) {
    // Get the array map.
    $data = [];
    $items = [];
    $ret = [];
    $item_class = $object->getItemDefinition()->getClass();

    // Derive the Xero type from the item list item definition.
    if (in_array('Drupal\xero\TypedData\XeroTypeInterface', class_implements($item_class))) {
      $plural_name = $item_class::getXeroProperty('plural_name');
      $name = $item_class::getXeroProperty('xero_name');
    }
    else {
      throw new \InvalidArgumentException('Invalid xero type used in object.');
    }

    /** @var \Drupal\xero\TypedData\XeroTypeInterface $item */
    foreach ($object as $n => $item) {
      $plugin_id = $item->getDataDefinition()->getDataType();

      // Normalize each property separately so that Xero lists can be nested
      // correctly. Otherwise the parent normalizer can be used fine.
      foreach ($item->getProperties() as $propName => $propData) {
        if ($propData !== NULL) {
          if ($propData instanceof XeroItemList) {
            $data[$propName] = $this->normalize($propData, $format, ['plugin_id' => $plugin_id], FALSE);
          }
          else {
            $data[$propName] = parent::normalize($propData, $format, ['plugin_id' => $plugin_id]);
          }
        }
      }

      $reducedData = $this->reduceEmpty($data);
      $items[] = $reducedData;
    }


    $item_count = count($items);
    // Serialization for XML varies depending on if its the root element or not
    // and if it has one or multiple items. JSON serialization always expects an
    // array of objects without the extra nesting unless it's the top element.
    if ($top || ($item_count === 1 && $format === 'xml')) {
      if ($item_count === 1) {
        $ret = $format === 'xml' ? [$name => $items[0]] : $items[0];
      }
      else {
        $ret = $format === 'xml' ? [$plural_name => [$name => $items]] : [$plural_name => $items];
      }
    }
    else {
      $ret = $format === 'xml' ? [$name => $items] : $items;
    }

    return $ret;
  }

  /**
   * Remove null values from normalized items.
   *
   * @param $value
   *   The value to reduce.
   * @return mixed
   *   Either FALSE or the value.
   */
  protected function reduceEmpty($value) {
    if (is_array($value)) {
      foreach ($value as $n => $item) {
        $item = $this->reduceEmpty($item);
        if ($item) {
          $value[$n] = $item;
        }
        else {
          unset($value[$n]);
        }
      }
    }
    else if (empty($value)) {
      return FALSE;
    }

    return $value;
  }

}
