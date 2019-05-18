<?php

namespace Drupal\commerce_shipengine\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipengine\ShipEngineRequestInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Url;

/**
 * @CommerceShippingMethod(
 *  id = "shipengine_handling_fee",
 *  label = @Translation("ShipEngine w/ 20% handling fee"),
 *  services = {
 *    "ups_standard_international" = @translation("UPS Standard®"),
 *    "ups_next_day_air_early_am" = @translation("UPS Next Day Air® Early"),
 *    "ups_worldwide_express" = @translation("UPS Worldwide Express®"),
 *    "ups_next_day_air" = @translation("UPS Next Day Air®"),
 *    "ups_ground_international" = @translation("UPS Ground® (International)"),
 *    "ups_worldwide_express_plus" = @translation("UPS Worldwide Express Plus®"),
 *    "ups_next_day_air_saver" = @translation("UPS Next Day Air Saver®"),
 *    "ups_worldwide_expedited" = @translation("UPS Worldwide Expedited®"),
 *    "ups_2nd_day_air_am" = @translation("UPS 2nd Day Air AM®"),
 *    "ups_2nd_day_air" = @translation("UPS Worldwide Express Plus®"),
 *    "ups_worldwide_saver" = @translation("UPS Worldwide Saver®"),
 *    "ups_2nd_day_air_international" = @translation("UPS 2nd Day Air® (International)"),
 *    "ups_3_day_select" = @translation("UPS 3 Day Select®"),
 *    "ups_ground" = @translation("UPS® Ground"),
 *    "ups_next_day_air_international" = @translation("UPS Next Day Air® (International)"),
 *    "usps_first_class_mail" = @translation("USPS First Class Mail"),
 *    "usps_media_mail" = @translation("USPS Media Mail"),
 *    "usps_parcel_select" = @translation("USPS Parcel Select Ground"),
 *    "usps_priority_mail" = @translation("USPS Priority Mail"),
 *    "usps_priority_mail_express" = @translation("USPS Priority Mail Express"),
 *    "usps_first_class_mail_international" = @translation("USPS First Class Mail Intl"),
 *    "usps_priority_mail_international" = @translation("USPS Priority Mail Intl"),
 *    "usps_priority_mail_express_international" = @translation("USPS Priority Mail Express Intl"),
 *  }
 * )
 */
class ShipEngineHandlingFee extends ShipEngine {

  /**
   * Add 20% fee to rates.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   *
   * @return \Drupal\commerce_shipping\ShippingRate[]
   *   The rates.
   */
  public function calculateRates(ShipmentInterface $shipment) {
    $rates = parent::calculateRates($shipment);

    foreach ($rates as &$rate) {
      $amount = $rate->getAmount();
      $amount = $amount->multiply('1.2');
      $rate->setAmount($amount);
    }

    return $rates;
  }

}
