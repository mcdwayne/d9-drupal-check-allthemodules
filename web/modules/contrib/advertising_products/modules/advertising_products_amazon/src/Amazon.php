<?php

namespace Drupal\advertising_products_amazon;

use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Lookup;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service class Amazon
 */
class Amazon {

  /**
   * @var string
   */
  protected $accessKey;

  /**
   * @var string
   */
  protected $accessSecret;

  /**
   * @var string
   */
  protected $associatesId;

  /**
   * @var string
   */
  protected $locale;

  /**
   * @var \ApaiIO\ApaiIO
   */
  protected $apaiIO;

  /**
   * Create instance of Amazon class.
   *
   * @param ConfigFactoryInterface $configFactory
   * @throws \Exception
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $configuration = $configFactory->get('amazon.settings');
    $this->accessKey = $configuration->get('access_key');
    $this->accessSecret = $configuration->get('access_secret');
    $this->associatesId = $configuration->get('associates_id');
    $this->locale = $configuration->get('locale');
    // Amazon access configuuration is required
    if (!isset($this->accessKey) || !isset($this->accessSecret) || !isset($this->associatesId)) {
      throw new \Exception('Missing Amazon access configuration.');
    }
  }

  /**
   * Extract ASIN from given url.
   *
   * @param string $url
   * @return mixed
   */
  public function getAsinFromUri($url) {

    // regex from http://littlegreenfootballs.com/article/44256_Tech_Note-_A_Regular_Expression_to_Extract_the_ASIN_Product_Code_From_Any_Amazon_URL
    $regex = '~
    (?:www\.)?              # optionally starts with www.
    ama?zo?n\.              # also allow shortened amzn.com URLs
    (?:
        com                 # match all Amazon domains
        |
        ca
        |
        co\.uk
        |
        co\.jp
        |
        de
        |
        fr
    )
    /
    (?:                     # here comes the stuff before the ASIN
        exec/obidos/ASIN/   # the possible components of a URL
        |
        o/
        |
        gp/product/
        |
        (?:                 # the dp/ format may contain a title
            (?:[^"\'/]*)/   # anything but a slash or quote
        )?                  # optional
        dp/
        |                   # if short format, nothing before ASIN
    )
    ([A-Z0-9]{10})          # capture group $1 contains the ASIN
    (?:                     # everything after the ASIN
        (?:/|\?|\#)         # beginning with /, ? or #
        (?:[^"\'\s]*)       # everything up to quote or white space
    )?                      # optional
~isx';

    if (preg_match($regex, $url, $matches)) {
      return $matches[1];
    }
    return FALSE;
  }

  /**
   * Fetch product data from API.
   *
   * @param string $product_id
   * @return array
   */
  public function itemLookup($product_id) {
    // Prepare apaiIO object
    $conf = new GenericConfiguration();
    $conf
      ->setCountry($this->locale)
      ->setAccessKey($this->accessKey)
      ->setSecretKey($this->accessSecret)
      ->setAssociateTag($this->associatesId)
      ->setResponseTransformer('\Drupal\amazon\LookupXmlToItemsArray');
    $this->apaiIO = new ApaiIO($conf);
    // Prepare Lookup object
    $lookup = new Lookup();
    $lookup->setItemId($product_id);
    $lookup->setResponseGroup(['ItemAttributes','Images','Offers', 'BrowseNodes']);
    // Do the API call
    $results = FALSE;
    try {
      $results = $this->apaiIO->runOperation($lookup);
    }
    catch (\Exception $e) {
      throw $e;
    }
    // Return product response
    return $results;
  }

  /**
   * Fetch primary image from API.
   *
   * @param \SimpleXMLElement $product
   *   A product XML fetched from the api
   * @return \Psr\Http\Message\ResponseInterface
   *   Response from server
   * @throws \Exception
   */
  public function retrievePrimaryImage($product) {
    $image_path = FALSE;
    // Find the primary image set and fetch the large image url
    if (isset($product->ImageSets)) {
      foreach ($product->ImageSets->children() as $imageSet) {
        if ($imageSet['Category'] == 'primary') {
          $image_path = (string)$imageSet->LargeImage->URL;
          break;
        }
      }
    }

    // Try to get large image when no 'primary' imageSet exists.
    if ($image_path === FALSE && isset($product->ImageSets)) {
      foreach ($product->ImageSets->children() as $imageSet) {
        $image_path = (string)$imageSet->LargeImage->URL;
        break;
      }
    }

    $image = FALSE;
    try {
      $image = $this->retrieveImage($image_path, $product->ASIN);
    }
    catch (\Exception $e) {
      throw $e;
    }
    return $image;
  }

  /**
   * Fetch product image from api.
   *
   * @param string $image_path
   *   A URL to the image
   * @param string $ASIN
   *  The ASIN of the product
   * @return \Psr\Http\Message\ResponseInterface
   *   Response from server
   * @throws \Exception
   */
  public function retrieveImage($image_path, $ASIN) {
    if ($image_path) {
      $tries = 0;
      do {
        $tries++;
        $image = \Drupal::httpClient()->request('GET', $image_path);
      } while (($image->getStatusCode() != 200 || !$image->getBody()) && $tries < 3);

      if (!$image->getBody()) {
        $error_msg = 'Error Message: ' . $image->getStatusCode() ? $image->getStatusCode() : "Couldn't retrieve image";
        throw new \Exception($error_msg, $ASIN, 'original');
      }

      if (!in_array($image->getHeader('content-type')[0], array('image/png', 'image/jpeg'))) {
       $error_msg = 'Error Message: Unexpected content type "' . $image->getHeader('content-type') . '"';
       throw new \Exception($error_msg, $ASIN, 'original');
      }
    }
    else {
       $error_msg = 'No image path found for ASIN "%s"';
       throw new \Exception(sprintf($error_msg, $ASIN));
    }

    if ($image) {
      return $image;
    }
    else {
      $error_msg = 'Could not find image for ASIN "%s"';
      throw new \Exception(sprintf($error_msg, $ASIN));
    }
  }

  /**
   * Extracts extra image URLs from product XML.
   *
   * @param \SimpleXMLElement $product
   *   A product XML fetched from the api
   *
   * @return array
   *   URLs of extra images
   */
  public function prepareExtraImageInfo($product) {
    $all_images = [];

    if (isset($product->ImageSets)) {
      foreach ($product->ImageSets->children() as $imageSet) {
        if ($imageSet->LargeImage->URL && strlen($imageSet->LargeImage->URL) > 10) {
          $extra_image_url = (string)$imageSet->LargeImage->URL;
          $all_images[$extra_image_url] = $extra_image_url;
        }
      }
    }

    return $all_images;
  }

  /**
   * Store info about all the images
   *
   * @param int $product_id
   *   A product entity id
   * @param array $urls
   *   An array of image urls
   *
   * @throws \Exception
   *   If storing failed.
   */
  public function storeExtraImageInfo($entity_id, $urls) {
    // check for data we have already stored
    if (count($urls)) {
      $query = \Drupal::database()->select('advertising_products_amazon_image_data', 'i');
      $query->fields('i', ['iid', 'url']);
      $query->condition('pid', $entity_id);
      $query->condition('url', $urls, 'IN');
      $already_there = $query->execute()->fetchAllAssoc('iid');
      // filter out the pre-existing values
      $new_urls = $urls;
      foreach ($already_there as $idx => $values) {
        if (isset($new_urls[$values->url])) {
          unset($new_urls[$values->url]);
        }
      }
      // insert any new urls
      foreach ($new_urls as $url) {
        $insert = \Drupal::database()->insert('advertising_products_amazon_image_data');
        $insert->fields(['pid', 'url']);
        $insert->values([$entity_id, $url]);
        $insert->execute();
      }
    }
  }
}

