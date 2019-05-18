<?php

namespace Drupal\commerce_usps\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * Provides the USPS shipping method.
 *
 * @CommerceShippingMethod(
 *  id = "usps",
 *  label = @Translation("USPS"),
 *  services = {
 *    "_0" = @translation("First-Class Mail Large Envelope/Letter/Parcel/Postcards"),
 *    "_1" = @translation("Priority Mail"),
 *    "_2" = @translation("Priority Mail Express Hold For Pickup"),
 *    "_3" = @translation("Priority Mail Express"),
 *    "_4" = @translation("USPS Retail Ground"),
 *    "_6" = @translation("Media Mail Parcel"),
 *    "_7" = @translation("Library Mail Parcel"),
 *    "_13" = @translation("Priority Mail Express Flat Rate Envelope"),
 *    "_15" = @translation("First-Class Mail Large Postcards"),
 *    "_16" = @translation("Priority Mail Flat Rate Envelope"),
 *    "_17" = @translation("Priority Mail Medium Flat Rate Box"),
 *    "_22" = @translation("Priority Mail Large Flat Rate Box"),
 *    "_23" = @translation("Priority Mail Express Sunday/Holiday Delivery"),
 *    "_25" = @translation("Priority Mail Express Sunday/Holiday Delivery Flat Rate Envelope"),
 *    "_27" = @translation("Priority Mail Express Flat Rate Envelope Hold For Pickup"),
 *    "_28" = @translation("Priority Mail Small Flat Rate Box"),
 *    "_29" = @translation("Priority Mail Padded Flat Rate Envelope"),
 *    "_30" = @translation("Priority Mail Express Legal Flat Rate Envelope"),
 *    "_31" = @translation("Priority Mail Express Legal Flat Rate Envelope Hold For Pickup"),
 *    "_32" = @translation("Priority Mail Express Sunday/Holiday Delivery Legal Flat Rate Envelope"),
 *    "_33" = @translation("Priority Mail Hold For Pickup"),
 *    "_34" = @translation("Priority Mail Large Flat Rate Box Hold For Pickup"),
 *    "_35" = @translation("Priority Mail Medium Flat Rate Box Hold For Pickup"),
 *    "_36" = @translation("Priority Mail Small Flat Rate Box Hold For Pickup"),
 *    "_37" = @translation("Priority Mail Flat Rate Envelope Hold For Pickup"),
 *    "_38" = @translation("Priority Mail Gift Card Flat Rate Envelope"),
 *    "_39" = @translation("Priority Mail Gift Card Flat Rate Envelope Hold For Pickup"),
 *    "_40" = @translation("Priority Mail Window Flat Rate Envelope"),
 *    "_41" = @translation("Priority Mail Window Flat Rate Envelope Hold For Pickup"),
 *    "_42" = @translation("Priority Mail Small Flat Rate Envelope"),
 *    "_43" = @translation("Priority Mail Small Flat Rate Envelope Hold For Pickup"),
 *    "_44" = @translation("Priority Mail Legal Flat Rate Envelope"),
 *    "_45" = @translation("Priority Mail Legal Flat Rate Envelope Hold For Pickup"),
 *    "_46" = @translation("Priority Mail Padded Flat Rate Envelope Hold For Pickup"),
 *    "_47" = @translation("Priority Mail Regional Rate Box A"),
 *    "_48" = @translation("Priority Mail Regional Rate Box A Hold For Pickup"),
 *    "_49" = @translation("Priority Mail Regional Rate Box B"),
 *    "_50" = @translation("Priority Mail Regional Rate Box B Hold For Pickup"),
 *    "_53" = @translation("First-Class/Package Service Hold For Pickup"),
 *    "_55" = @translation("Priority Mail Express Flat Rate Boxes"),
 *    "_56" = @translation("Priority Mail Express Flat Rate Boxes Hold For Pickup"),
 *    "_57" = @translation("Priority Mail Express Sunday/Holiday Delivery Flat Rate Boxes"),
 *    "_58" = @translation("Priority Mail Regional Rate Box C"),
 *    "_59" = @translation("Priority Mail Regional Rate Box C Hold For Pickup"),
 *    "_61" = @translation("First-Class/Package Service"),
 *    "_62" = @translation("Priority Mail Express Padded Flat Rate Envelope"),
 *    "_63" = @translation("Priority Mail Express Padded Flat Rate Envelope Hold For Pickup"),
 *    "_64" = @translation("Priority Mail Express Sunday/Holiday Delivery Padded Flat Rate Envelope"),
 *    "_77" = @translation("Parcel Select Ground"),
 *  }
 * )
 */
class USPS extends USPSBase {

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    // Only attempt to collect rates if an address exists on the shipment.
    if ($shipment->getShippingProfile()->get('address')->isEmpty()) {
      return [];
    }

    // Only attempt to collect rates for US addresses.
    if ($shipment->getShippingProfile()->get('address')->country_code != 'US') {
      return [];
    }

    // Make sure a package type is set on the shipment.
    $this->setPackageType($shipment);

    return $this->uspsRateService->getRates($shipment);
  }

}
