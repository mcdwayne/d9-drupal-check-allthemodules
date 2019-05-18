<?php

namespace Drupal\commerce_usps;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_usps\Event\USPSEvents;
use Drupal\commerce_usps\Event\USPSShipmentEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use USPS\RatePackage;

/**
 * Class that sets the shipment details needed for the USPS request.
 *
 * @package Drupal\commerce_usps
 */
class USPSShipmentBase implements USPSShipmentInterface {

  /**
   * Event disptcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The commerce shipment entity.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $commerceShipment;

  /**
   * The USPS rate package entity.
   *
   * @var \USPS\RatePackage
   */
  protected $uspsPackage;

  /**
   * The shipping method configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * USPSShipmentBase constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Store the shipping method configuration.
   *
   * @param array $configuration
   */
  public function setConfig(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Get the RatePackage object.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment entity.
   *
   * @return \USPS\RatePackage
   *   The RatePackage object.
   */
  public function getPackage(ShipmentInterface $commerce_shipment) {
    $this->commerceShipment = $commerce_shipment;

    if (!$this->uspsPackage) {
      $this->buildPackage($commerce_shipment);
      $this->alterPackage();
    }

    return $this->uspsPackage;
  }

  /**
   * Allow rate to be altered.
   */
  public function alterPackage() {
    // Allow other modules to alter the rate request before it's submitted.
    $shipment_event = new USPSShipmentEvent($this->uspsPackage, $this->commerceShipment);
    $this->eventDispatcher->dispatch(USPSEvents::AFTER_BUILD_SHIPMENT, $shipment_event);
  }

  /**
   * Get the USPS RatePackage object.
   *
   * @return \USPS\RatePackage
   *   The RatePackage object.
   */
  public function buildPackage() {
    $this->uspsPackage = new RatePackage();
    return $this->uspsPackage;
  }

  /**
   * Sets the package dimensions.
   */
  public function setDimensions() {
    $package_type = $this->getPackageType();
    if (!empty($package_type)) {
      $length = ceil($package_type->getLength()->convert('in')->getNumber());
      $width = ceil($package_type->getWidth()->convert('in')->getNumber());
      $height = ceil($package_type->getHeight()->convert('in')->getNumber());
      $size = $length > 12 || $width > 12 || $height > 12 ? 'LARGE' : 'REGULAR';
      $this->uspsPackage->setField('Size', $size);
      $this->uspsPackage->setField('Width', $width);
      $this->uspsPackage->setField('Length', $length);
      $this->uspsPackage->setField('Height', $height);
      $this->uspsPackage->setField('Girth', 0);
    }
  }

  /**
   * Sets the package weight.
   */
  protected function setWeight() {
    $weight = $this->commerceShipment->getWeight();

    if ($weight->getNumber() > 0) {
      $ounces = $weight->convert('oz')->getNumber();

      $this->uspsPackage->setPounds(floor($ounces / 16));
      $this->uspsPackage->setOunces($ounces % 16);
    }
  }

  /**
   * Determine the package type for the shipment.
   *
   * @return \Drupal\commerce_shipping\Plugin\Commerce\PackageType\PackageTypeInterface
   *   The package type.
   */
  protected function getPackageType() {
    // If the package is set on the shipment, use that.
    if (!empty($this->commerceShipment->getPackageType())) {
      return $this->commerceShipment->getPackageType();
    }
    // TODO return default shipment for shipping method.
  }

}
