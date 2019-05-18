<?php

namespace Drupal\commerce_opp;

/**
 * Defines the brand repository interface.
 */
interface BrandRepositoryInterface {

  /**
   * Gets the brand with the given ID.
   *
   * @param string $id
   *   The brand ID. For example: 'VISA'.
   *
   * @return \Drupal\commerce_opp\Brand
   *   The brand.
   *
   * @throws \InvalidArgumentException
   *   If the given brand ID is unknown.
   */
  public function getBrand($id);

  /**
   * Gets all available brands.
   *
   * @return \Drupal\commerce_opp\Brand[]
   *   The available brands.
   */
  public function getBrands();

  /**
   * Gets the labels of all available brands.
   *
   * @return array
   *   The labels, keyed by ID.
   */
  public function getBrandLabels();

  /**
   * Gets all available card account brands.
   *
   * @return \Drupal\commerce_opp\Brand[]
   *   The available card account brands.
   */
  public function getCardAccountBrands();

  /**
   * Gets the labels of all available card account brands.
   *
   * @return array
   *   The labels, keyed by ID.
   */
  public function getCardAccountBrandLabels();

  /**
   * Gets all available bank account brands.
   *
   * @return \Drupal\commerce_opp\Brand[]
   *   The available bank account brands.
   */
  public function getBankAccountBrands();

  /**
   * Gets the labels of all available bank account brands.
   *
   * @return array
   *   The labels, keyed by ID.
   */
  public function getBankAccountBrandLabels();

  /**
   * Gets all available virtual account brands.
   *
   * @return \Drupal\commerce_opp\Brand[]
   *   The available virtual account brands.
   */
  public function getVirtualAccountBrands();

  /**
   * Gets the labels of all available virtual account brands.
   *
   * @return array
   *   The labels, keyed by ID.
   */
  public function getVirtualAccountBrandLabels();

}
