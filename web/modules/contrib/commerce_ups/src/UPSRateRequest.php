<?php

namespace Drupal\commerce_ups;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Ups\Rate;
use Ups\Entity\RateInformation;

/**
 * Class UPSRateRequest.
 *
 * @package Drupal\commerce_ups
 */
class UPSRateRequest extends UPSRequest implements UPSRateRequestInterface {

  /**
   * A shipping method configuration array.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The UPS Shipment object.
   *
   * @var \Drupal\commerce_ups\UPSShipmentInterface
   */
  protected $upsShipment;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * UPSRateRequest constructor.
   *
   * @param \Drupal\commerce_ups\UPSShipmentInterface $ups_shipment
   *   The UPS shipment object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(UPSShipmentInterface $ups_shipment, LoggerChannelFactoryInterface $logger_factory) {
    $this->upsShipment = $ups_shipment;
    $this->logger = $logger_factory->get(UPSRateRequestInterface::LOGGER_CHANNEL);
  }

  /**
   * Fetch rates from the UPS API.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment.
   * @param \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface $shipping_method
   *   The shipping method.
   *
   * @throws \Exception
   *   Exception when required properties are missing.
   *
   * @return array
   *   An array of ShippingRate objects.
   */
  public function getRates(ShipmentInterface $commerce_shipment, ShippingMethodInterface $shipping_method) {
    $rates = [];

    try {
      $auth = $this->getAuth();
    }
    catch (\Exception $e) {
      $this->logger->error(
        new TranslatableMarkup(
          'Unable to fetch authentication config for UPS. Please check your shipping method configuration.'
        )
      );
      return [];
    }

    $request = new Rate(
      $auth['access_key'],
      $auth['user_id'],
      $auth['password'],
      $this->useIntegrationMode()
    );

    try {
      $shipment = $this->upsShipment->getShipment($commerce_shipment, $shipping_method);

      // Enable negotiated rates, if enabled.
      if ($this->getRateType()) {
        $rate_information = new RateInformation();
        $rate_information->setNegotiatedRatesIndicator(TRUE);
        $rate_information->setRateChartIndicator(FALSE);
        $shipment->setRateInformation($rate_information);
      }

      // Shop Rates.
      $ups_rates = $request->shopRates($shipment);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $ups_rates = [];
    }

    if (!empty($ups_rates->RatedShipment)) {
      foreach ($ups_rates->RatedShipment as $ups_rate) {
        $service_code = $ups_rate->Service->getCode();

        // Only add the rate if this service is enabled.
        if (!in_array($service_code, $this->configuration['services'])) {
          continue;
        }

        // Use negotiated rates if they were returned.
        if ($this->getRateType() && !empty($ups_rate->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue)) {
          $cost = $ups_rate->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue;
          $currency = $ups_rate->NegotiatedRates->NetSummaryCharges->GrandTotal->CurrencyCode;
        }
        // Otherwise, use the default rates.
        else {
          $cost = $ups_rate->TotalCharges->MonetaryValue;
          $currency = $ups_rate->TotalCharges->CurrencyCode;
        }

        $price = new Price((string) $cost, $currency);
        $service_name = $ups_rate->Service->getName();

        $shipping_service = new ShippingService(
          $service_code,
          $service_name
        );
        $rates[] = new ShippingRate(
          $service_code,
          $shipping_service,
          $price
        );
      }
    }
    return $rates;
  }

  /**
   * Gets the rate type: whether we will use negotiated rates or standard rates.
   *
   * @return bool
   *   Returns true if negotiated rates should be requested.
   */
  public function getRateType() {
    return boolval($this->configuration['rate_options']['rate_type']);
  }

}
