<?php

namespace Drupal\geoip\Plugin\GeoLocator;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Local geolocation provider.
 *
 * @GeoLocator(
 *   id = "local",
 *   label = "Local dataset",
 *   description = "Uses local MaxmindDB dataset for geolocation",
 *   weight = 0
 * )
 */
class Local extends GeoLocatorBase {

  const GEOLITE_CITY_DB = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';
  const GEOLITE_COUNTRY_DB = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz';

  protected $scheme = 'public';

  /**
   * {@inheritdoc}
   */
  public function geolocate($ip_address) {
    $reader = $this->getReader();
    // If the reader could not be initiated, then back out.
    if (!$reader) {
      return NULL;
    }

    try {
      $record = $reader->country($ip_address);

      if ($this->geoIpConfig->get('debug')) {
        $this->logger->notice($this->t('Discovered %ip_address in the Maxmind local database', [
          '%ip_address' => $ip_address,
        ]));
      }

      return $record->country->isoCode;
    }
    catch (AddressNotFoundException $e) {
      if ($this->geoIpConfig->get('debug')) {
        $this->logger->notice($this->t('Unable to look up %ip_address in the Maxmind local database', [
          '%ip_address' => $ip_address,
        ]));
      }
      return NULL;
    }
    catch (InvalidDatabaseException $e) {
      $this->logger->error($this->t('The Maxmind database reader reported an invalid or corrupt database.'));
      return NULL;
    }
  }

  /**
   * Get a dataset reader.
   *
   * @return \GeoIp2\Database\Reader|null
   *   Reader that can parse Mindmax datasets.
   */
  protected function getReader() {
    $city_uri = $this->getScheme() . '://GeoLite2-City.mmdb';
    $country_uri = $this->getScheme() . '://GeoLite2-Country.mmdb';

    if (file_exists($city_uri)) {
      $reader = new Reader($city_uri);
    }
    elseif (file_exists($country_uri)) {
      $reader = new Reader($country_uri);
    }
    else {
      // No dataset is installed.
      return NULL;
    }

    return $reader;
  }

  /**
   * Get the current file scheme.
   *
   * @return string
   *   The file scheme.
   */
  public function getScheme() {
    return $this->scheme;
  }

}
