<?php

namespace Drupal\commerce_usps\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the USPS international shipping method.
 *
 * @CommerceShippingMethod(
 *  id = "usps_international",
 *  label = @Translation("USPS International"),
 *  services = {
 *    "_1" = @translation("Priority Mail Express International"),
 *    "_2" = @translation("Priority Mail International"),
 *    "_4" = @translation("Global Express Guaranteed (GXG)"),
 *    "_5" = @translation("Global Express Guaranteed Document"),
 *    "_6" = @translation("Global Express Guaranteed Non-Document Rectangular"),
 *    "_7" = @translation("Global Express Guaranteed Non-Document Non-Rectangular"),
 *    "_8" = @translation("Priority Mail International Flat Rate Envelope"),
 *    "_9" = @translation("Priority Mail International Medium Flat Rate Box"),
 *    "_10" = @translation("Priority Mail Express International Flat Rate Envelope"),
 *    "_11" = @translation("Priority Mail International Large Flat Rate Box"),
 *    "_12" = @translation("USPS GXG Envelopes"),
 *    "_13" = @translation("First-Class Mail International Letter"),
 *    "_14" = @translation("First-Class Mail International Large Envelope"),
 *    "_15" = @translation("First-Class Package International Service"),
 *    "_16" = @translation("Priority Mail International Small Flat Rate Box"),
 *    "_17" = @translation("Priority Mail Express International Legal Flat Rate Envelope"),
 *    "_18" = @translation("Priority Mail International Gift Card Flat Rate Envelope"),
 *    "_19" = @translation("Priority Mail International Window Flat Rate Envelope"),
 *    "_20" = @translation("Priority Mail International Small Flat Rate Envelope"),
 *    "_21" = @translation("First-Class Mail International Postcard"),
 *    "_22" = @translation("Priority Mail International Legal Flat Rate Envelope"),
 *    "_23" = @translation("Priority Mail International Padded Flat Rate Envelope"),
 *    "_24" = @translation("Priority Mail International DVD Flat Rate priced box"),
 *    "_25" = @translation("Priority Mail International Large Video Flat Rate priced box"),
 *    "_26" = @translation("Priority Mail Express International Flat Rate Boxes"),
 *    "_27" = @translation("Priority Mail Express International Padded Flat Rate Envelope"),
 *  }
 * )
 */
class USPSInternational extends USPSBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.commerce_package_type'),
      $container->get('commerce_usps.usps_rate_request_international')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    // Only attempt to collect rates if an address exists on the shipment.
    if ($shipment->getShippingProfile()->get('address')->isEmpty()) {
      return [];
    }

    // Do not attempt to collect rates for US addresses.
    if ($shipment->getShippingProfile()->get('address')->country_code == 'US') {
      return [];
    }

    // Make sure a package type is set on the shipment.
    $this->setPackageType($shipment);

    return $this->uspsRateService->getRates($shipment);
  }

}
