<?php

namespace Drupal\geolocation_address_link;

use Drupal\geolocation\GeolocationCore;
use CommerceGuys\Addressing\Address;
use CommerceGuys\Addressing\Formatter\PostalLabelFormatter;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AddressToGeo.
 *
 * This class is designed to take an array of address values, convert it to a
 * string value, then geocode the value.
 *
 * The geocoder expects a string value, and it expects values ordered
 * appropriately for the country of the address. Using the PostalLabelFormatter
 * ensures that the string sent to the geocoder is formatted correctly no
 * matter which country the address contains.
 *
 * @package Drupal\geolocation_address_link
 */
class AddressToGeo {

  /**
   * The geocoder manager.
   *
   * @var \Drupal\geolocation\GeolocationCore
   */
  protected $geolocationManager;

  /**
   * The geocoder
   */
  protected $geocoder;

  /**
   * The address format repository.
   *
   * @var \CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface
   */
  protected $addressFormatRepository;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * The postal label formatter.
   *
   * @var \CommerceGuys\Addressing\Formatter\PostalLabelFormatter;
   */
  protected $formatter;

  /**
   * Format the address as a postal label sent from what country?
   */
  protected $formatCountry;

  /**
   * Format the address in what language?
   */
  protected $formatLanguage;

  /**
   * Constructor.
   */
  public function __construct(GeolocationCore $geolocationManager, AddressFormatRepositoryInterface $address_format_repository, CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository) {

    $this->geolocationManager = $geolocationManager;
    $this->addressFormatRepository = $address_format_repository;
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;

    // Set up the geocoder and address formatter.
    $this->setGeocoder();
    $this->setFormatCountry();
    $this->setFormatLanguage();
    $this->setFormatter();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('geolocation.core'),
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * Set the geocoder.
   */
  public function setGeocoder($geocoder = 'google_geocoding_api') {
    $this->geocoder = $this->geolocationManager->getGeocoderManager()->getGeocoder($geocoder);
  }

  /**
   * Set the format country.
   *
   * The country format determines if the country name is appended to
   * a postal address. If set to 'US', the country name would be added for all
   * countries except the USA (it assumes this is a postal label for mail
   * sent from the US). This probably matches the geocoding service's
   * expectations.
   */
  public function setFormatCountry($country = 'US') {
    $this->formatCountry = $country;
  }

  /**
   * Set the format language.
   *
   * The language format is for deciding what language to use to create the
   * address that we send to the geocoding service. The geocoordinates
   * will be the same in any language, so it shouldn't matter.
   */
  public function setFormatLanguage($language = 'en') {
    $this->formatLanguage = $language;
  }

  /**
   * Set formatter.
   *
   * You could override this method to use the DefaultFormatter() instead.
   */
  public function setFormatter() {
    $default_options = [
      'locale' => $this->formatLanguage,
      'origin_country' => $this->formatCountry
    ];
    $this->formatter = new PostalLabelFormatter($this->addressFormatRepository, $this->countryRepository, $this->subdivisionRepository, $default_options);
  }

  /**
   * Convert an address array into a string address suitable for geocoding.
   *
   * Expects array structured like the Address module as the input values.
   * @see \Drupal\address\Element\Address::applyDefaults().
   */
  public function addressArrayToString( array $address_array ) {

    // Make sure the address_array has all values populated.
    $address_array = \Drupal\address\Element\Address::applyDefaults($address_array);

    // Use the Address formatter to create a string ordered appropriately
    // for the country in the address.
    $address = new \CommerceGuys\Addressing\Address();
    $address = $address
        ->withCountryCode($address_array['country_code'])
        ->withPostalCode($address_array['postal_code'])
        ->withAdministrativeArea($address_array['administrative_area'])
        ->withDependentLocality($address_array['dependent_locality'])
        ->withLocality($address_array['locality'])
        ->withAddressLine1($address_array['address_line1'])
        ->withAddressLine2($address_array['address_line2'])
        ->withOrganization($address_array['organization']);

    $address_string = $this->formatter->format($address);

    // Clean up the returned address to turn it into a single line of text.
    $address_string = str_replace("\n", ' ', $address_string);
    $address_string = str_replace("<br>", ' ', $address_string);
    $address_string = strip_tags($address_string);
    return $address_string;
  }

  /**
   * Geocode an address.
   *
   * Note that the returned address may be cleaned up and expanded
   * by the address formatter and the geocoding service.
   *
   * @param mixed address
   *  Either a string address or an associative array using the architecture
   *  provided by the Address module.
   *  @see \Drupal\address\Element\Address::applyDefaults().
   *
   * @return array
   *   'lat': string LATITUDE
   *   'long': string LONGITUDE
   *   'data': array Map settings
   */
  public function geocode($address, $map_size = '400x400') {
    if (is_array($address)) {
      $address = $this->addressArrayToString($address);
    }
    $address = str_replace(' ', '+', $address);
    if ($result = $this->geocoder->geocode($address)) {

      // Store the boundary and address data returned by the geocoding service,
      // and use the boundary to compute a logical zoom setting for this specific
      // location.
      return [
        'lat' => $result['location']['lat'],
        'lng' => $result['location']['lng'],
        'data' => [
          'boundary' => $result['boundary'],
          'address' => $result['address'],
          'zoom' => $this->getZoom($result['boundary'], $map_size),
        ],
      ];
    }
    return FALSE;
  }

  /**
   * A method to roughly calculate the right zoom level for a place.
   *
   * Uses the boundary information and an assumption about the pixel width
   * that the map will be displayed at.
   *
   * @param array $boundary
   *   An array of boundary values as returned by Google's geocoding service.
   * @param integer $pixel_width
   *   The estimated pixel width of the map display.
   *
   * @return integer $zoom
   *   A zoom level that will display everything in the boundary box.
   *
   * @see https://stackoverflow.com/questions/6048975/google-maps-v3-how-to-calculate-the-zoom-level-for-a-given-bounds
   */
  public function calcZoom($boundary, $pixel_width = 800) {
    $globe_width = 256; // a constant in Google's map projection
    $west = $boundary['lng_south_west'];
    $east = $boundary['lng_north_east'];
    $angle = $east - $west;
    if ($angle < 0) $angle += 360;
    $zoom = round(log($pixel_width * 360 / $angle / $globe_width) / log(2));
    return $zoom;
  }

  /**
   * A method to roughly calculate the right zoom level for a place.
   *
   * Uses the boundary information and an assumption about the pixel width
   * that the map will be displayed at.
   *
   * @param array $boundary
   *   An array of boundary values as returned by Google's geocoding service.
   * @param integer $map_size
   *   The estimated pixel dimensions of the map display.
   *
   * @return integer $zoom
   *   A zoom level that will display everything in the boundary box.
   *
   * @see https://stackoverflow.com/questions/6048975/google-maps-v3-how-to-calculate-the-zoom-level-for-a-given-bounds
   */
  function getZoom($boundary, $map_size = '400x400') {

    // Map dimensions
    $dimensions = explode('x', $map_size);
    $width = $dimensions[0];
    $height = $dimensions[1];

    // The entire world fits in a 256 pixel square at zoom level 0.
    $world_height = 256;
    $world_width = 256;

    $zoom_max = 21;

    $ne_lat = $boundary['lat_north_east'];
    $ne_lng = $boundary['lng_north_east'];
    $sw_lat = $boundary['lat_south_west'];
    $sw_lng = $boundary['lng_south_west'];

    $latFraction = ($this->latRad($ne_lat) - $this->latRad($sw_lat)) / pi();

    $lngDiff = $ne_lng - $sw_lng;
    $lngFraction = (($lngDiff < 0) ? ($lngDiff + 360) : $lngDiff) / 360;

    $latZoom = $this->zoom($height, $world_height, $latFraction);
    $lngZoom = $this->zoom($width, $world_width, $lngFraction);

    return min($latZoom, $lngZoom, $zoom_max);
  }

  /**
   * Helper for getZoom().
   */
  function latRad($lat) {
    $sin = sin($lat * pi() / 180);
    $radX2 = log((1 + $sin) / (1 - $sin)) / 2;
    return max(min($radX2, pi()), -pi()) / 2;
  }

  /**
   * Helper for getZoom().
   */
  function zoom($mapPx, $worldPx, $fraction) {
    return floor(log($mapPx / $worldPx / $fraction) / log(2));
  }

}
