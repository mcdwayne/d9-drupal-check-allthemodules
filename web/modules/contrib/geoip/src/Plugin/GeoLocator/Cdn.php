<?php

namespace Drupal\geoip\Plugin\GeoLocator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * CDN geolocation provider.
 *
 * @GeoLocator(
 *   id = "cdn",
 *   label = "CDN",
 *   description = "Checks for geolocation headers sent by CDN services",
 *   weight = -10
 * )
 */
class Cdn extends GeoLocatorBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function geolocate($ip_address) {

    if ($this->checkCloudflare()) {
      $country_code = $this->checkCloudflare();
    }
    elseif ($this->checkCloudFront()) {
      $country_code = $this->checkCloudFront();
    }
    elseif ($this->checkCustomHeader()) {
      $country_code = $this->checkCustomHeader();
    }
    else {
      // Could not geolocate based off of CDN.
      if ($this->geoIpConfig->get('debug')) {
        $this->logger->notice($this->t('Unable to look up %ip_address via CDN header', [
          '%ip_address' => $ip_address,
        ]));
      }
      return NULL;
    }

    if ($this->geoIpConfig->get('debug')) {
      $this->logger->notice($this->t('Discovered %ip_address via CDN header', [
        '%ip_address' => $ip_address,
      ]));
    }

    return $country_code;
  }

  /**
   * Check for Cloudflare geolocation header.
   *
   * @return string
   *   The country code specified in the header.
   */
  protected function checkCloudflare() {
    return (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : NULL;
  }

  /**
   * Check for Amazon CloudFront geolocation header.
   *
   * @return string
   *   The country code specified in the header.
   */
  protected function checkCloudFront() {
    return (!empty($_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'])) ? $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] : NULL;
  }

  /**
   * Check for a custom geolocation header.
   *
   * @return string
   *   The country code specified in the header.
   */
  protected function checkCustomHeader() {
    // @todo: Implement setting for custom header to check.
    return NULL;
  }

}
