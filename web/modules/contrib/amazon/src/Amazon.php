<?php
/**
 * @file
 * Contains Drupal\amazon\Amazon
 */

namespace Drupal\amazon;

use Drupal\amazon\AmazonRequest;
use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Lookup;

/**
 * Provides methods that interfaces with the Amazon Product Advertising API.
 *
 * @package Drupal\amazon
 */
class Amazon {

  /**
   * The server environment variables for (optionally) specifying the access
   * key and secret.
   */
  const AMAZON_ACCESS_KEY = 'AMAZON_ACCESS_KEY';
  const AMAZON_ACCESS_SECRET = 'AMAZON_ACCESS_SECRET';

  /**
   * @var \ApaiIO\ApaiIO
   *   The Amazon API object.
   */
  protected $apaiIO;

  /**
   * Provides an Amazon object for calling the Amazon API.
   *
   * @param string $associatesId
   *   The Amazon Associates ID (a.k.a. tag).
   * @param string $accessKey
   *   (optional) Access key to use for all API requests. If not specified, the
   *   access key is determined from other system variables.
   * @param string $accessSecret
   *   (optional) Access secret to use for all API requests. If not specified,
   *   the access key is determined from other system variables.
   * @param string $locale
   *   (optional) Which locale to run queries against. Valid values include: de,
   *   com, co.uk, ca, fr, co.jp, it, cn, es, in.
   */
  public function __construct($associatesId, $accessKey = '', $accessSecret = '', $locale = 'com') {
    if (empty($accessKey)) {
      $accessKey = self::getAccessKey();
      if (!$accessKey) {
        throw new \InvalidArgumentException('Configuration missing: Amazon access key.');
      }
    }
    if (empty($accessSecret)) {
      $accessSecret = self::getAccessSecret();
      if (!$accessSecret) {
        throw new \InvalidArgumentException('Configuration missing: Amazon access secret.');
      }
    }

    $conf = new GenericConfiguration();
    $conf
      ->setCountry($locale)
      ->setAccessKey($accessKey)
      ->setSecretKey($accessSecret)
      ->setAssociateTag($associatesId)
      ->setResponseTransformer('\Drupal\amazon\LookupXmlToItemsArray');
    $this->apaiIO = new ApaiIO($conf);
  }

  /**
   * Returns the secret key needed for API calls.
   *
   * @return string|bool
   *   String on success, FALSE otherwise.
   */
  static public function getAccessSecret() {
    // Use credentials from environment variables, if available.
    $secret = getenv(self::AMAZON_ACCESS_SECRET);
    if ($secret) {
      return $secret;
    }

    // If not, use Drupal config variables. (Automatically handles overrides
    // in settings.php.)
    $secret = \Drupal::config('amazon.settings')->get('access_secret');
    if ($secret) {
      return $secret;
    }

    return FALSE;
  }

  /**
   * Returns the access key needed for API calls.
   *
   * @return string|bool
   *   String on success, FALSE otherwise.
   */
  static public function getAccessKey() {
    // Use credentials from environment variables, if available.
    $key = getenv(self::AMAZON_ACCESS_KEY);
    if ($key) {
      return $key;
    }

    // If not, use Drupal config variables. (Automatically handles overrides
    // in settings.php.)
    $key = \Drupal::config('amazon.settings')->get('access_key');
    if ($key) {
      return $key;
    }

    return FALSE;
  }

  /**
   * Gets information about an item, or array of items, from Amazon.
   *
   * @param array|string $items
   *   A string containing a single ASIN, or an array of ASINs, to look up.
   *
   * @return array
   *   An array of SimpleXMLElement objects representing the response from
   *   Amazon.
   */
  public function lookup($items) {
    if (empty($items)) {
      throw new \InvalidArgumentException('Calling lookup without anything to lookup!');
    }
    if (!is_array($items)) {
      $items = [$items];
    }

    $results = [];
    // Cannot ask for info from more than 10 items in a single call.
    foreach(array_chunk($items, 10) as $asins) {
      $lookup = new Lookup();
      $lookup->setItemIds($asins);
      $lookup->setResponseGroup(['Small', 'Images']);
      $results = array_merge($results, $this->apaiIO->runOperation($lookup));
    }
    return $results;
  }

}
