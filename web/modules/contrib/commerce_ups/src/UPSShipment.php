<?php

namespace Drupal\commerce_ups;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;
use Drupal\physical\LengthUnit;
use Drupal\physical\WeightUnit;
use Ups\Entity\Package as UPSPackage;
use Ups\Entity\Address;
use Ups\Entity\PackageWeight;
use Ups\Entity\PackagingType;
use Ups\Entity\ShipFrom;
use Ups\Entity\Shipment as APIShipment;
use Ups\Entity\Dimensions;

/**
 * Class to create and return a UPS API shipment object.
 *
 * @package Drupal\commerce_ups
 */
class UPSShipment extends UPSEntity implements UPSShipmentInterface {

  /**
   * The commerce shipment.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $shipment;

  /**
   * The shipping method.
   *
   * @var \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface
   */
  protected $shippingMethod;

  /**
   * Creates and returns a UPS API shipment object.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The commerce shipment.
   * @param \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface $shipping_method
   *   The shipping method.
   *
   * @return \Ups\Entity\Shipment
   *   A Ups API shipment object.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getShipment(ShipmentInterface $shipment, ShippingMethodInterface $shipping_method) {
    $this->shipment = $shipment;
    $this->shippingMethod = $shipping_method;
    $api_shipment = new APIShipment();

    $this->setShipTo($api_shipment);
    $this->setShipFrom($api_shipment);
    $this->setPackage($api_shipment);

    return $api_shipment;
  }

  /**
   * Sets the ship to for a given shipment.
   *
   * @param \Ups\Entity\Shipment $api_shipment
   *   A Ups API shipment object.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function setShipTo(APIShipment $api_shipment) {
    /** @var \CommerceGuys\Addressing\AddressInterface $address */
    $address = $this->shipment->getShippingProfile()->get('address')->first();
    $to_address = new Address();

    $to_address->setAddressLine1($address->getAddressLine1());
    $to_address->setAddressLine2($address->getAddressLine2());
    $to_address->setCity($address->getLocality());
    $to_address->setCountryCode($address->getCountryCode());
    $to_address->setStateProvinceCode($address->getAdministrativeArea());
    $to_address->setPostalCode($address->getPostalCode());

    $api_shipment->getShipTo()->setAddress($to_address);
  }

  /**
   * Sets the ship from for a given shipment.
   *
   * @param \Ups\Entity\Shipment $api_shipment
   *   A Ups API shipment object.
   */
  public function setShipFrom(APIShipment $api_shipment) {
    /** @var \CommerceGuys\Addressing\AddressInterface $address */
    $address = $this->shipment->getOrder()->getStore()->getAddress();
    $from_address = new Address();

    $from_address->setAddressLine1($address->getAddressLine1());
    $from_address->setAddressLine2($address->getAddressLine2());
    $from_address->setCity($address->getLocality());
    $from_address->setCountryCode($address->getCountryCode());
    $from_address->setStateProvinceCode($address->getAdministrativeArea());
    $from_address->setPostalCode($address->getPostalCode());

    $ship_from = new ShipFrom();
    $ship_from->setAddress($from_address);

    $api_shipment->setShipFrom($ship_from);
  }

  /**
   * Sets the package for a given shipment.
   *
   * @param \Ups\Entity\Shipment $api_shipment
   *   A Ups API shipment object.
   */
  protected function setPackage(APIShipment $api_shipment) {
    $package = new UPSPackage();

    $this->setDimensions($package);
    $this->setWeight($package);
    $this->setPackagingType($package);

    $api_shipment->addPackage($package);
  }

  /**
   * Package dimension setter.
   *
   * @param \Ups\Entity\Package $ups_package
   *   A Ups API package object.
   */
  public function setDimensions(UPSPackage $ups_package) {
    $dimensions = new Dimensions();

    $valid_unit = $this->getValidDimensionsUnit();

    // Rounding dimensions since decimals are not allowed by the UPS API.
    $dimensions->setHeight(ceil($this
      ->getPackageType()
      ->getHeight()
      ->convert($valid_unit)
      ->getNumber()
    ));
    $dimensions->setWidth(ceil($this
      ->getPackageType()
      ->getWidth()
      ->convert($valid_unit)
      ->getNumber()
    ));
    $dimensions->setLength(ceil($this
      ->getPackageType()
      ->getLength()
      ->convert($valid_unit)
      ->getNumber()
    ));
    $dimensions->setUnitOfMeasurement($this
      ->setUnitOfMeasurement($this
        ->getUnitOfMeasure($valid_unit)
      ));

    $ups_package->setDimensions($dimensions);
  }

  /**
   * Define the package weight.
   *
   * @param \Ups\Entity\Package $ups_package
   *   A package object from the Ups API.
   */
  public function setWeight(UPSPackage $ups_package) {
    $weight = $this->shipment->getWeight()->convert($this->getValidWeightUnit());

    $ups_package_weight = new PackageWeight();
    $ups_package_weight->setWeight($weight->getNumber());
    $ups_package_weight->setUnitOfMeasurement($this
      ->setUnitOfMeasurement($this
        ->getUnitOfMeasure($weight->getUnit()
      ))
    );

    $ups_package->setPackageWeight($ups_package_weight);
  }

  /**
   * Sets the package type for a UPS package.
   *
   * @param \Ups\Entity\Package $ups_package
   *   A Ups API package entity.
   */
  public function setPackagingType(UPSPackage $ups_package) {
    $remote_id = $this->getPackageType()->getRemoteId();
    $attributes = new \stdClass();
    $attributes->Code = !empty($remote_id) && $remote_id != 'custom' ? $remote_id : PackagingType::PT_UNKNOWN;

    $ups_package->setPackagingType(new PackagingType($attributes));
  }

  /**
   * Determine the package type for the shipment.
   *
   * @return \Drupal\commerce_shipping\Plugin\Commerce\PackageType\PackageTypeInterface
   *   The package type.
   */
  protected function getPackageType() {
    // If the package is set on the shipment, use that.
    if (!empty($this->shipment->getPackageType())) {
      return $this->shipment->getPackageType();
    }

    // Or use the default package type for the shipping method.
    return $this->shippingMethod->getDefaultPackageType();
  }

  /**
   * Get valid dimensions measurement unit for a current store address.
   *
   * @return string
   *   Valid measurement unit.
   */
  protected function getValidDimensionsUnit() {
    /** @var \CommerceGuys\Addressing\Address $address */
    $address = $this->shipment->getOrder()->getStore()->getAddress();

    return $address->getCountryCode() == 'US' ? LengthUnit::INCH : LengthUnit::CENTIMETER;
  }

  /**
   * Get valid weight measurement unit for a current store address.
   *
   * @return string
   *   Valid measurement unit.
   */
  protected function getValidWeightUnit() {
    /** @var \CommerceGuys\Addressing\Address $address */
    $address = $this->shipment->getOrder()->getStore()->getAddress();

    return $address->getCountryCode() == 'US' ? WeightUnit::POUND : WeightUnit::KILOGRAM;
  }

}
