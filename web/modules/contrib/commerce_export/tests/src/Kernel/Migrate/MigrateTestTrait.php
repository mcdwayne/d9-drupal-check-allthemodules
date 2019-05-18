<?php

namespace Drupal\Tests\commerce_export\Kernel\Migrate;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Helper function to test migrations.
 */
trait MigrateTestTrait {

  /**
   * Asserts a product attribute entity.
   *
   * @param string $name
   *   The attribute id.
   * @param string $label
   *   The expected attribute label.
   * @param string $element_type
   *   The expected element type of the attribute.
   */
  protected function assertProductAttributeEntity($name, $label, $element_type) {
    $attribute = ProductAttribute::load($name);
    $this->assertInstanceOf(ProductAttribute::class, $attribute);
    $this->assertSame($label, $attribute->label());
    $this->assertSame($element_type, $attribute->getElementType());
  }

  /**
   * Asserts a product attribute value entity.
   *
   * @param string $id
   *   The attribute value id.
   * @param string $attribute_id
   *   The expected product attribute value id.
   * @param string $name
   *   The expected name of the product attribute value.
   * @param string $label
   *   The expected label of the product attribute value.
   * @param string $weight
   *   The expected weight of the product attribute value.
   */
  protected function assertProductAttributeValueEntity($id, $attribute_id, $name, $label, $weight) {
    $attribute_value = ProductAttributeValue::load($id);
    $this->assertInstanceOf(ProductAttributeValue::class, $attribute_value);
    $this->assertSame($attribute_id, $attribute_value->getAttributeId());
    $this->assertSame($name, $attribute_value->getName());
    $this->assertSame($label, $attribute_value->label());
    $this->assertSame($weight, $attribute_value->getWeight());
  }

  /**
   * Asserts a product.
   *
   * @param int $id
   *   The product id.
   * @param int $owner_id
   *   The uid for this billing profile.
   * @param string $title
   *   The title of the product.
   * @param string $is_published
   *   The published status of the product.
   * @param array $store_ids
   *   The ids of the stores for this product.
   * @param array $variations
   *   The variation of this product.
   * @param array $terms
   *   An array of taxonomy field names and values.
   * @param array $suggested
   *   An array of suggested products.
   */
  public function assertProductEntity($id, $owner_id, $title, $is_published, array $store_ids, array $variations, array $terms, array $suggested) {
    $product = Product::load($id);
    $this->assertInstanceOf(Product::class, $product);
    $this->assertSame($owner_id, $product->getOwnerId());
    $this->assertSame($title, $product->getTitle());
    $this->assertSame($is_published, $product->isPublished());
    $this->assertSame($store_ids, $product->getStoreIds());
    $this->assertSame($variations, $product->getVariationIds());
    foreach ($terms as $name => $data) {
      $this->assertSame($data, $product->get($name)->getValue(), "Taxonomy $name is incorrect.");
    }
    $this->assertSame($suggested, $product->get('field_suggested_products')->getValue());
  }

  /**
   * Asserts a product variation.
   *
   * @param int $id
   *   The product variation id.
   * @param int $owner_id
   *   The uid for this billing profile.
   * @param string $sku
   *   The SKU.
   * @param string $price_number
   *   The price.
   * @param string $price_currency
   *   The currency code.
   * @param string $product_id
   *   The id of the product.
   * @param string $variation_title
   *   The title.
   * @param string $variation_bundle
   *   The order item type.
   * @param string $variation_created_time
   *   The title.
   * @param string $variation_changed_time
   *   The order item type.
   * @param array $attributes
   *   Array of attribute names and id.
   * @param array $files
   *   Array of file information.
   */
  public function assertProductVariationEntity($id, $owner_id, $sku, $price_number, $price_currency, $product_id, $variation_title, $variation_bundle, $variation_created_time, $variation_changed_time, array $attributes, array $files) {
    $variation = ProductVariation::load($id);
    $this->assertInstanceOf(ProductVariation::class, $variation);
    $this->assertSame($owner_id, $variation->getOwnerId());
    $this->assertSame($sku, $variation->getSku());
    $this->assertSame($price_number, $variation->getPrice()->getNumber());
    $this->assertSame($price_currency, $variation->getPrice()
      ->getCurrencyCode());
    $this->assertSame($product_id, $variation->getProductId());
    $this->assertSame($variation_title, $variation->getTitle());
    $this->assertSame($variation_bundle, $variation->getOrderItemTypeId());
    if ($variation_created_time != NULL) {
      $this->assertSame($variation_created_time, $variation->getCreatedTime());
    }
    if ($variation_changed_time != NULL) {
      $this->assertSame($variation_changed_time, $variation->getChangedTime());
    }
    foreach ($attributes as $name => $data) {
      if ($data) {
        $this->assertSame($data['id'], $variation->getAttributeValueId($name));
        $this->assertSame($data['value'], $variation->getAttributeValue($name)
          ->getName());
      }
    }
    foreach ($files as $name => $data) {
      if ($data) {
        $this->assertSame([$data], $variation->get($name)->getValue(), "File data for $name is incorrect.");
      }
      else {
        $this->assertSame($data, $variation->get($name)->getValue());
      }
    }
  }

}
