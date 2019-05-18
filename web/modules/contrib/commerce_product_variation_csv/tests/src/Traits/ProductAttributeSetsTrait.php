<?php

namespace Drupal\Tests\commerce_product_variation_csv\Traits;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductAttributeValueInterface;

trait ProductAttributeSetsTrait {

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * Creates an attribute field and set of attribute values.
   *
   * @param string $variation_type_id
   *   The variation type ID.
   * @param string $name
   *   The attribute field name.
   * @param array $options
   *   Associative array of key name values. [red => Red].
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   Array of attribute entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createAttributeSet($variation_type_id, $name, array $options): array {
    $attribute = ProductAttribute::create([
      'id' => $name,
      'label' => ucfirst($name),
    ]);
    $attribute->save();
    $this->attributeFieldManager->createField($attribute, $variation_type_id);

    $attribute_set = [];
    foreach ($options as $key => $value) {
      $attribute_set[$key] = $this->createAttributeValue($name, $value);
    }

    return $attribute_set;
  }

  /**
   * Creates an attribute value.
   *
   * @param string $attribute
   *   The attribute ID.
   * @param string $name
   *   The attribute value name.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   *   The attribute value entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createAttributeValue($attribute, $name): ProductAttributeValueInterface {
    $attribute_value = ProductAttributeValue::create([
      'attribute' => $attribute,
      'name' => $name,
    ]);
    $attribute_value->save();

    return $attribute_value;
  }

}
