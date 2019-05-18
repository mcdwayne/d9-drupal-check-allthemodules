<?php

namespace Drupal\commerce_usps;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_usps\Event\USPSEvents;
use Drupal\commerce_usps\Event\USPSRateRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use USPS\Rate;

/**
 * Class USPSRateRequest.
 *
 * @package Drupal\commerce_usps
 */
abstract class USPSRateRequestBase extends USPSRequest implements USPSRateRequestInterface {

  /**
   * The commerce shipment entity.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $commerceShipment;

  /**
   * The configuration array from a CommerceShippingMethod.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The USPS rate request API.
   *
   * @var \USPS\Rate
   */
  protected $uspsRequest;

  /**
   * The USPS Shipment object.
   *
   * @var \Drupal\commerce_usps\USPSShipmentInterface
   */
  protected $uspsShipment;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * USPSRateRequest constructor.
   *
   * @param \Drupal\commerce_usps\USPSShipmentInterface $usps_shipment
   *   The USPS shipment object.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(USPSShipmentInterface $usps_shipment, EventDispatcherInterface $event_dispatcher) {
    $this->uspsShipment = $usps_shipment;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig(array $configuration) {
    parent::setConfig($configuration);
    // Set the configuration on the USPS Shipment service.
    $this->uspsShipment->setConfig($configuration);
  }

  /**
   * Fetch rates from the USPS API.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment.
   *
   * @throws \Exception
   *   Exception when required properties are missing.
   *
   * @return array
   *   An array of ShippingRate objects.
   */
  public function getRates(ShipmentInterface $commerce_shipment) {
    // Validate a commerce shipment has been provided.
    if (empty($commerce_shipment)) {
      throw new \Exception('Shipment not provided');
    }

    // Set the necessary info needed for the request.
    $this->setShipment($commerce_shipment);

    // Build the rate request.
    $this->buildRate();

    // Allow others to alter the rate.
    $this->alterRate();

    // Fetch the rates.
    $this->logRequest();
    $this->uspsRequest->getRate();
    $this->logResponse();
    $response = $this->uspsRequest->getArrayResponse();

    return $this->resolveRates($response);
  }

  /**
   * Build the rate request.
   */
  public function buildRate() {
    $this->uspsRequest = new Rate(
      $this->configuration['api_information']['user_id']
    );
    $this->setMode();
  }

  /**
   * Allow rate to be altered.
   */
  public function alterRate() {
    // Allow other modules to alter the rate request before it's submitted.
    $rateRequestEvent = new USPSRateRequestEvent($this->uspsRequest, $this->commerceShipment);
    $this->eventDispatcher->dispatch(USPSEvents::BEFORE_RATE_REQUEST, $rateRequestEvent);
  }

  /**
   * Set the commerce shipment.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment entity.
   */
  public function setShipment(ShipmentInterface $commerce_shipment) {
    $this->commerceShipment = $commerce_shipment;
  }

  /**
   * Logs the request data.
   */
  public function logRequest() {
    if (!empty($this->configuration['options']['log']['request'])) {
      $request = $this->uspsRequest->getPostData();
      \Drupal::logger('commerce_usps')->info('@message', ['@message' => print_r($request, TRUE)]);
    }
  }

  /**
   * Logs the response data.
   */
  public function logResponse() {
    if (!empty($this->configuration['options']['log']['response'])) {
      \Drupal::logger('commerce_usps')->info('@message', ['@message' => print_r($this->uspsRequest->getResponse(), TRUE)]);
    }
  }

  /**
   * Set the mode to either test/live.
   */
  protected function setMode() {
    $this->uspsRequest->setTestMode($this->isTestMode());
  }

  /**
   * Get an array of USPS packages.
   *
   * @return array
   *   An array of USPS packages.
   */
  public function getPackages() {
    // @todo: Support multiple packages.
    return [$this->uspsShipment->getPackage($this->commerceShipment)];
  }

  /**
   * Utility function to clean the USPS service name.
   *
   * @param string $service
   *   The service id.
   *
   * @return string
   *   The cleaned up service id.
   */
  public function cleanServiceName($service) {
    // Remove the html encoded trademark markup since it's
    // not supported in radio labels.
    $service = str_replace('&lt;sup&gt;&#8482;&lt;/sup&gt;', '', $service);
    $service = str_replace('&lt;sup&gt;&#174;&lt;/sup&gt;', '', $service);
    return $service;
  }

}
