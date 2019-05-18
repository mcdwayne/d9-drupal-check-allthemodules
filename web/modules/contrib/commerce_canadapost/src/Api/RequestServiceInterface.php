<?php

namespace Drupal\commerce_canadapost\Api;

use Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod\CanadaPost;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Interface for the Canada Post API Service.
 *
 * @package Drupal\commerce_canadapost
 */
interface RequestServiceInterface {

  /**
   * Fetch the Canada Post API settings, first from the method, then, the store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   A store entity, if the api settings are for a store.
   * @param \Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod\CanadaPost $shipping_method
   *   The shipping method.
   *
   * @throws \Exception
   *
   * @return array
   *   Returns the api settings.
   */
  public function getApiSettings(StoreInterface $store = NULL, CanadaPost $shipping_method = NULL);

  /**
   * Returns a Canada Post config to pass to the request service api.
   *
   * @param array $api_settings
   *   The Canada Post API settings.
   *
   * @return \CanadaPost\Rating
   *   The Canada Post request service object.
   */
  public function getRequestConfig(array $api_settings);

}
