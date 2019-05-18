<?php

namespace Drupal\pricespider\Service;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface PriceSpiderServiceInterface.
 */
interface PriceSpiderServiceInterface {

  /**
   * Returns boolean value if entity bundle is marked as a product type.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle name.
   *
   * @return bool
   *   Boolean TRUE or FALSE
   */
  public function isProductType($entity_type, $bundle);

  /**
   * Return an associative array of Entity type/bundles marked as product types.
   *
   * Array is keyed by Entity Type, followed by Bundle name, and sku
   * field name.
   *
   * @return array
   *   The content type and field name.
   */
  public function getProductTypes();

  /**
   * Remove an entity type or bundle as a product type.
   *
   * @param string $entity_type
   *   Entity type.
   * @param bool $bundle
   *   Entity bundle (optional)
   */
  public function removeProductType($entity_type, $bundle = FALSE);

  /**
   * Return associative array of fields that can be used for SKU.
   *
   * Key should be field name and value is the human readable label.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle name.
   *
   * @return array
   *   Returns associative array of fields.
   */
  public function getSkuFieldOptions($entity_type, $bundle);

  /**
   * Return the field for the entity bundle that holds the SKU value.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle name.
   *
   * @return string
   *   Name of the field
   */
  public function getSkuField($entity_type, $bundle);

  /**
   * Set the field to be used to retrieve sku for an entity bundle.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle name.
   * @param string $sku_field
   *   Sku field name.
   *
   * @return mixed
   *   Define which field to use for the sku.
   */
  public function setSkuField($entity_type, $bundle, $sku_field = '');

  /**
   * Returns the SKU value for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return string
   *   Value from sku field.
   */
  public function getSkuValue(EntityInterface $entity);

  /**
   * Returns URI path to Where to Buy page.
   *
   * @param bool $absolute
   *   Boolean flag if to return relative uri or absolute.
   *
   * @return string
   *   Uri string.
   */
  public function getWTBUri($absolute = FALSE);

  /**
   * Return a full or filtered array of Pricespider metatags.
   *
   * @param array $tag_names
   *   Array of tag names to filter.
   *
   * @return array
   *   Array of Drupal build arrays for HTML meta tags.
   */
  public function getMetaTags(array $tag_names = []);

}
