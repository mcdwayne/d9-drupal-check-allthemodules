<?php

namespace Drupal\commerce_tax_wa\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_tax\Resolver\TaxRateResolverInterface;
use Drupal\commerce_tax\TaxRate;
use Drupal\commerce_tax\TaxZone;
use Drupal\profile\Entity\ProfileInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Returns the tax zone's default tax rate.
 */
class WaTaxRateResolver implements TaxRateResolverInterface {

  /**
   * @var \GuzzleHttp\ClientInterface*/
  protected $client;

  /**
   * WaTaxRateResolver constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(TaxZone $zone, OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    $zones = $zone->getTerritories();
    /** @var \CommerceGuys\Addressing\Zone\ZoneTerritory $territory */
    $territory = reset($zones);
    if ($territory->getCountryCode() == 'US' && $territory->getAdministrativeArea() == 'WA') {
      $rates = $zone->getRates();

      // Take the default rate, or fallback to the first rate.
      $resolved_rate = reset($rates);

      $street1 = $customer_profile->address->address_line1;
      $street2 = $customer_profile->address->address_line2;
      $city = $customer_profile->address->locality;
      $zip = $customer_profile->address->postal_code;

      $url = 'https://webgis.dor.wa.gov/webapi/addressrates.aspx?output=xml' .
        '&addr=' . urlencode(trim($street1 . ' ' . $street2)) .
        '&city=' . urlencode(trim($city)) .
        '&zip=' . urlencode(trim($zip));

      try {
        $response = $this->client->get($url);
        if ($response->getStatusCode() == 200) {
          /* Example response:
          * ﻿<?xml version="1.0" encoding="utf-8"?><response loccode="1726" localrate=".036" rate=".101" code="2"><addressline code="1726" street="W HARRISON ST" househigh="100" houselow="100" evenodd="E" state="WA" zip="98119" plus4="4116" period="Q42017" rta="Y" ptba="King PTBA" cez="" /><rate name="SEATTLE" code="1726" staterate=".065" localrate=".036" /></response>
          */
          $xml = new \SimpleXMLElement($response->getBody());
          $definition = [
            'id' => (string) $xml['loccode'],
            'label' => (string) $xml->rate['name'] . ' ' . (string) $xml['loccode'],
            'percentages' => [
              ['number' => (string) $xml['rate'], 'start_date' => '2000-01-01'],
            ],
          ];
          $resolved_rate = new TaxRate($definition);
        }
      }
      catch (RequestException $e) {
        \Drupal::logger('commerce_tax_wa')
          ->warning('Error from Washington Tax Lookup service, Using default rate.');
        watchdog_exception('commerce_tax_wa', $e);
      }


      return $resolved_rate;
    }
  }

}
