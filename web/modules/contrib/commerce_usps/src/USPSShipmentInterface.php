<?php

namespace Drupal\commerce_usps;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * Interface to create and return a USPS API shipment object.
 *
 * @package Drupal\commerce_usps
 */
interface USPSShipmentInterface {

  /**
   * Returns an initialized rate package object.
   *
   * This method should invoke ::buildPackage and
   * ::alterPackage before returning the RatePackage
   * object.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   A Drupal Commerce shipment entity.
   *
   * @return \USPS\RatePackage
   *   The rate package entity.
   */
  public function getPackage(ShipmentInterface $commerce_shipment);

  /**
   * Build the RatePackage.
   */
  public function buildPackage();

  /**
   * Alter the RatePackage.
   */
  public function alterPackage();

  /**
   * Set the shipping method configuration.
   *
   * @param array $configuration
   *   The shipping method configuration.
   */
  public function setConfig(array $configuration);

}
