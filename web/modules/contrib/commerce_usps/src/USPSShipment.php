<?php

namespace Drupal\commerce_usps;

use USPS\Address;
use USPS\RatePackage;

/**
 * Class that sets the shipment details needed for the USPS request.
 *
 * @package Drupal\commerce_usps
 */
class USPSShipment extends USPSShipmentBase implements USPSShipmentInterface {

  /**
   * Returns an initialized rate package object.
   *
   * @return \USPS\RatePackage
   *   The rate package entity.
   */
  public function buildPackage() {
    parent::buildPackage();

    $this->setService();
    $this->setShipFrom();
    $this->setShipTo();
    $this->setWeight();
    $this->setContainer();
    $this->setDimensions();
    $this->setPackageSize();
    $this->setExtraOptions();

    return $this->uspsPackage;
  }

  /**
   * Sets the ship to for a given shipment.
   */
  protected function setShipTo() {
    /** @var \CommerceGuys\Addressing\Address $address */
    $address = $this->commerceShipment->getShippingProfile()->get('address')->first();
    $to_address = new Address();
    $to_address->setAddress($address->getAddressLine1());
    $to_address->setApt($address->getAddressLine2());
    $to_address->setCity($address->getLocality());
    $to_address->setState($address->getAdministrativeArea());
    $to_address->setZip5($address->getPostalCode());

    $this->uspsPackage->setZipDestination($address->getPostalCode());
  }

  /**
   * Sets the ship from for a given shipment.
   */
  protected function setShipFrom() {
    /** @var \CommerceGuys\Addressing\Address $address */
    $address = $this->commerceShipment->getOrder()->getStore()->getAddress();
    $from_address = new Address();
    $from_address->setAddress($address->getAddressLine1());
    $from_address->setCity($address->getLocality());
    $from_address->setState($address->getAdministrativeArea());
    $from_address->setZip5($address->getPostalCode());
    $from_address->setZip4($address->getPostalCode());

    $this->uspsPackage->setZipOrigination($address->getPostalCode());
  }

  /**
   * Sets the package size.
   */
  protected function setPackageSize() {
    $this->uspsPackage->setSize(RatePackage::SIZE_REGULAR);
  }

  /**
   * Sets the services for the shipment.
   */
  protected function setService() {
    $this->uspsPackage->setService(RatePackage::SERVICE_ALL);
  }

  /**
   * Sets the package container for the shipment.
   */
  protected function setContainer() {
    $this->uspsPackage->setContainer(RatePackage::CONTAINER_VARIABLE);
  }

  /**
   * Sets any extra options specific to the shipment like ship date etc.
   */
  protected function setExtraOptions() {
    $this->uspsPackage->setField('Machinable', TRUE);
    $this->uspsPackage->setField('ShipDate', $this->getProductionDate());
  }

  /**
   * Returns the current date.
   *
   * @return string
   *   The current date, formatted.
   */
  protected function getProductionDate() {
    $date = date('Y-m-d', strtotime("now"));

    return $date;
  }

}
