<?php

namespace Drupal\advertising_products;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Defines interface for advertising product providers.
 */
interface AdvertisingProductsProviderInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Extracts the product ID from given url.
   *
   * @param string $url
   */
  public function getProductIdFromUrl($url);

  /**
   * Gets entity ID from given product ID
   *
   * @param string $product_id
   */
  public function getEntityIdFromProductId($product_id);

  /**
   * Creates advertising product.
   *
   * @param string $product_id
   * @param int $entity_id
   *
   * @return
   */
  public function fetchProductOnTheFly($product_id, $entity_id = NULL);

  /**
   * Search for advertising product.
   *
   * @param string $input
   */
  public function searchProduct($input);

  /**
   * Retrieves product data through provider API.
   *
   * @param type $product_id
   */
  public function queryProduct($product_id);

  /**
   * Creates advertising product entity.
   *
   * @param mixed $product_data
   */
  public function saveProduct($product_data, $entity_id = NULL);

  /**
   * Returns the prefix for the product image file.
   *
   * @param mixed $product_data
   *
   * @return string
   */
  public function getImagePrefix($product_data);

  /**
   * @param \Psr\Http\Message\ResponseInterface $response
   *
   * @param $prefix
   *
   * @return \Drupal\file\FileInterface
   */
  public function saveImage(ResponseInterface $response, $prefix);

  /**
   * Updates advertising product entity.
   *
   * @param string $product_id
   * @param string $entity_id
   */
  public function updateProduct($product_id, $entity_id);

  /**
   * Changes product status to "0"
   *
   * @param string $entity_id
   */
  public function setProductInactive($entity_id);


  /**
   * Called, when AdvertisingProductsAutocompleteWidget is submitted.
   *
   * @param array $values - The values added by the FormWidget
   *
   * @return boolean
   *   If it was successful.
   */
  public function submitFieldWidget(array $values);

}
