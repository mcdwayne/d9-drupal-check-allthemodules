<?php

namespace Drupal\affiliates_connect_amazon\Plugin\AffiliatesNetwork;

use Drupal\affiliates_connect\AffiliatesNetworkBase;
use Drupal\affiliates_connect_amazon\AmazonItems;
use Drupal\affiliates_connect_amazon\AmazonLocale;
use Drupal\Core\Url;

/**
 * Contains Plugin ID and Plugin definition info for affiliates_connect_amazon.
 *
 * @AffiliatesNetwork(
 *  id = "affiliates_connect_amazon",
 *  label = @Translation("Amazon"),
 *  description = @Translation("Plugin provided by affiliates_connect_amazon."),
 * )
 */
class AmazonConnect extends AffiliatesNetworkBase implements AmazonConnectInterface {
  /**
   * Stores the options used in this request.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Stores the generated signature link.
   *
   * @var array
   */
  protected $url;

  /**
   * Stores the results of this request when executed.
   *
   * @var array
   */
  protected $results = [];

  /**
   * The access key secret for the AWS account authorized to use the Product
   * Advertising API.
   *
   * @var string
   */
  protected $accessSecret;

  /**
   * The access key ID for the AWS account authorized to use the Product
   * Advertising API.
   *
   * @var string
   */
  protected $accessKey;

  /**
   * The associates ID (or tag) for the Product Advertising API account.
   *
   * @var string
   */
  protected $associatesId;

  /**
   * Location
   *
   * @var string
   */
  protected $locale;

  /**
   * The domain of the endpoint for making Product Advertising API requests.
   *
   * @TODO: generalize to include locale
   *
   * @see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/AnatomyOfaRESTRequest.html
   *
   * @var string
   */
  protected $amazonRequestRoot = 'webservices.amazon';

  /**
   * The path to the endpoint for making Product Advertising API requests.
   *
   * @see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/AnatomyOfaRESTRequest.html
   *
   * @var string
   */
  protected $amazonRequestPath = '/onca/xml';

  /**
   * Set Credentials.
   *
   * @param string $accessSecret
   *   The access key secret for the AWS account authorized to use the Product
   *   Advertising API.
   * @param string $accessKey
   *   The access key ID for the AWS account authorized to use the
   *   Product Advertising API. This can be passed into the request as an
   *   option.
   * @param string $associatesId
   *   The associates ID for the Product Advertising API account.
   *   This can be passed into the request as an option.
   * @param string $locale
   *   Location
   */
  public function setCredentials($secretKey, $accessKey, $associatesId, $locale = 'IN') {
    $this->accessSecret = $secretKey;
    $this->accessKey = $accessKey;
    $this->associatesId = $associatesId;
    $this->locale = AmazonLocale::getURL($locale);
  }

  /**
   * Prepares the request for execution.
   *
   * @see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/rest-signature.html
   */
  protected function prepare() {
    if (empty($this->options['AWSAccessKeyId'])) {
      if (empty($this->accessKey)) {
        throw new \InvalidArgumentException('Missing AWSAccessKeyId. Need to be passed as an option or set in the constructor.');
      }
      else {
        $this->setOption('AWSAccessKeyId', $this->accessKey);
      }
    }

    if (empty($this->options['AssociateTag'])) {
      if (empty($this->associatesId)) {
        throw new \InvalidArgumentException('Missing AssociateTag. Need to be passed as an option or set in the constructor.');
      }
      else {
        $this->setOption('AssociateTag', $this->associatesId);
      }
    }

    // Add a Timestamp (UTC) .
    $this->options['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");

    // Sort options by key.
    ksort($this->options);

    // To build the Signature, we need a very specific string format. We also
    // have to handle urlencoding so that the hashed string matches the encoded
    // string received by Amazon.
    $encodedOptions = [];
    foreach ($this->options as $name => $value) {
      if (is_array($value)) {
        $value = implode(',', $value);
      }
      $name = str_replace("%7E", "~", rawurlencode($name));
      $value = str_replace("%7E", "~", rawurlencode($value));
      $encodedOptions[] = $name . '=' . $value;
    }

    $string = join("\n", [
      'GET',
      $this->amazonRequestRoot . $this->locale,
      $this->amazonRequestPath,
      implode('&', $encodedOptions),
    ]);
    $signature = $this->createSignature($string);
    return $signature;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareLink() {
    $endpoint = 'http://' . $this->amazonRequestRoot . $this->locale . $this->amazonRequestPath;
    $signature = $this->prepare();
    $url = Url::fromUri($endpoint, ['query' => $this->options]);
    $url = $url->toString() . '&Signature=' . $signature;
    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $data = $this->get($this->url, []);

    if (isset($data) && $data->getStatusCode() == 200) {
      $xml = new \SimpleXMLElement($data->getBody());
      $this->cleanResult($xml);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($name, $value) {
    if (empty($name)) {
      throw new \InvalidArgumentException('Invalid option name: ' . $name);
    }
    if ($name == 'Timestamp' || $name == 'Signature') {
      // Automatically calculated, so we ignore these.
      return $this;
    }

    $this->options[$name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    foreach($options as $name => $value) {
      if (empty($name)) {
        throw new \InvalidArgumentException('Invalid option name: ' . $name);
      }
      $this->setOption($name, $value);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * @inheritdoc.
   */
  public function getLink() {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function createSignature(string $signatureString) {
    return urlencode(
      base64_encode(
        hash_hmac('sha256', $signatureString, $this->accessSecret, true)
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function itemLookup(string $asin)
  {
    $this->setOptions([
      'Operation' => 'ItemLookup',
      'Service' => 'AWSECommerceService',
      'ResponseGroup' => 'ItemAttributes,Offers,Reviews,Images',
      'ItemId' => $asin,
    ]);
    $this->prepareLink();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function itemSearch(string $keyword, string $searchIndex) {
    $this->setOptions([
      'Operation' => 'ItemSearch',
      'Service' => 'AWSECommerceService',
      'ResponseGroup' => 'ItemAttributes,Offers,Reviews,Images',
      'Keywords' => $keyword,
      'SearchIndex' => $searchIndex,
    ]);
    $this->prepareLink();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanResult($XML)
  {
    $this->results = AmazonItems::createWithXml($XML);
  }

  /**
   * {@inheritdoc}
   */
  public function unsetOption(string $name)
  {
    unset($this->options[$name]);
  }
}
