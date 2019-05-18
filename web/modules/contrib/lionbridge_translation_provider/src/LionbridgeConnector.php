<?php

namespace Drupal\lionbridge_translation_provider;

use Drupal\Component\Serialization\Json;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TMGMTException;
use GuzzleHttp\Exception\ClientException;

/**
 * Connects Lionbridge API to TMGMT.
 */
class LionbridgeConnector {

  const QUOTE_STATUS_PENDING     = 'Pending';
  const QUOTE_STATUS_ERROR       = 'Error';
  const QUOTE_STATUS_CALCULATING = 'Calculating';
  const QUOTE_STATUS_AUTHORIZED  = 'Authorized';
  const QUOTE_STATUS_COMPLETE    = 'Complete';

  const AUTH_STATUS_NOT_CREATED = 'Not created';

  /**
   * API url.
   *
   * @var string
   */
  protected $endPoint;

  /**
   * Api version - Check current version here http://api-docs.liondemand.com/.
   *
   * @var string
   */
  protected $apiVersion = '2016-03-15';

  /**
   * List of allowed currencies.
   *
   * @var array
   */
  protected $allowedCurrencies = ['USD', 'EUR', 'GBP'];

  /**
   * Optional Purchase Order Number.
   *
   * @var string
   */
  protected $poNumber;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Construct class and set default params.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   Constructs the connector.
   */
  public function __construct(TranslatorInterface $translator) {
    $this->keyId    = $translator->getSetting('access_key_id');
    $this->secretId = $translator->getSetting('access_key');
    $this->currency = $translator->getSetting('currency');
    $this->endPoint = $translator->getSetting('endpoint');
    $this->poNumber = $translator->getSetting('po_number');
    $this->client   = \Drupal::httpClient();
  }

  /**
   * Return xml with account information.
   *
   * @return string
   *   Gets Lionbridge account info.
   */
  public function accountInformation() {
    return $this->getResource('/api/account/info');
  }

  /**
   * Gets the PO number.
   *
   * @return int
   *   The Lionbridge PO number.
   */
  public function getPoNumber() {
    return $this->poNumber;
  }

  /**
   * Gets the endpoint.
   *
   * @return string
   *   The Lionbridge API endpoint.
   */
  public function getEndPoint() {
    return $this->endPoint;
  }

  /**
   * Gets the currency property.
   *
   * @return string
   *   The currency (USD, EUR, GBP).
   */
  public function getCurrency() {
    return $this->currency;
  }

  /**
   * Gets the list of allowed currencies.
   *
   * @return array
   *   A list of currencies.
   */
  public function getAllowedCurrencies() {
    return $this->allowedCurrencies;
  }

  /**
   * Return xml with services details.
   *
   * @return object
   *   Return xml with services details.
   */
  public function listServices() {
    return $this->getResource('/api/services');
  }

  /**
   * Return xml with details for a single project.
   *
   * @param string $quote_id
   *   The quote id returned from Lionbridge.
   *
   * @return object
   *   Return xml with details for a single quote.
   *
   * @throws TMGMTException
   */
  public function getQuote($quote_id) {
    if (empty($quote_id)) {
      throw new TMGMTException('Empty Quote ID.');
    }

    return $this->getResource('/api/quote/' . $quote_id);
  }

  /**
   * Authorize quote for a given job.
   *
   * @param array $quote
   *   The quote returned from getQuote().
   *
   * @return object
   *   Return xml with success or error.
   */
  public function authorizeQuote(array $quote) {
    $po_number = $this->getPoNumber();

    $quote_xml = [
      '#theme' => 'lionbridge_authorize_quote',
      '#quote' => $quote,
      '#service_id' => $quote['Projects']['Project']['ServiceID'],
      '#total_transactions' => count($quote['Projects']['Project']['Files']['File']),
      '#po_number' => $po_number,
      '#project' => $quote['Projects']['Project'],
      '#source_language' => $quote['Projects']['Project']['SourceLanguage']['LanguageCode'],
      '#target_language' => $quote['Projects']['Project']['TargetLanguages']['TargetLanguage']['LanguageCode'],
    ];

    $resource = $this->sendResource(
      "/api/quote/{$quote['QuoteID']}/authorize",
      \Drupal::service('renderer')->render($quote_xml),
      ['Content-type' => 'text/xml']
    );

    return $resource;
  }

  /**
   * Rejects a quote from Lionbridge.
   *
   * @param string $quote_id
   *   The quote id returned from Lionbridge.
   *
   * @return object
   *   Return xml with details for a single quote.
   *
   * @throws TMGMTException
   */
  public function rejectQuote($quote_id) {
    if (empty($quote_id)) {
      throw new TMGMTException('Empty Quote ID.');
    }

    return $this->sendResource('/api/quote/' . $quote_id . '/reject');
  }

  /**
   * Returns xml with lis of locales.
   *
   * @return object
   *   Returns xml with lis of locales.
   */
  public function listLocales() {
    return $this->getResource('/api/locales');
  }

  /**
   * Returns the content of a translated file.
   *
   * @param string $asset_id
   *   The asset id returned from Lionbridge.
   * @param string $lang_code
   *   The language code of the desired translation. Ex: 'fr-fr'.
   *
   * @return object
   *   The translated text.
   *
   * @throws TMGMTException
   */
  public function getFileTranslation($asset_id, $lang_code) {
    if (empty($asset_id)) {
      throw new TMGMTException('Empty File asset ID.');
    }
    if (empty($lang_code)) {
      throw new TMGMTException('Empty Language code.');
    }

    return $this->getResource('/api/files/' . $asset_id . '/' . $lang_code);
  }

  /**
   * Upload file for future translation.
   *
   * @param string $language_code
   *   The language code of the source text. Ex: 'fr-fr'.
   * @param string $file_name
   *   The name of the file.
   * @param string $content_type
   *   The content type of the file.
   * @param string $file_content
   *   The file contents.
   *
   * @return array
   *   The file information from Lionbridge, including the AssetID.
   */
  public function addFile($language_code, $file_name, $content_type, $file_content) {
    $resource = '/api/files/add/' . $language_code . '/' . $file_name;
    return $this->sendResource($resource, $file_content, ['Content-type' => $content_type]);
  }

  /**
   * Creates a project from previously uploaded files.
   *
   * @param string $project_title
   *   The title of the project from the checkout settings form.
   * @param string $service_id
   *   The ID of the service chosen on the checkout settings form.
   * @param string $source_language
   *   The language code of the source data in the form of 'en-us'.
   * @param array $target_languages
   *   The language code of the desired translation in the form of 'fr-fr'.
   * @param array $files
   *   An array of asset IDs for a file-based translation.
   *
   * @return mixed
   *   The project data from Lionbridge.
   */
  public function addProject($project_title, $service_id, $source_language, array $target_languages, array $files = array()) {
    $project_xml = [
      '#theme' => 'lionbridge_add_project',
      '#project_title' => $project_title,
      '#service_id' => $service_id,
      '#source_language' => $source_language,
      '#target_languages' => $target_languages,
      '#files' => $files,
    ];

    $result = $this->sendResource(
      '/api/projects/add',
      \Drupal::service('renderer')->render($project_xml),
      ['Content-type' => 'text/xml']
    );

    return $result;
  }

  /**
   * Generates a quote for uploaded files.
   *
   * @param string $project_id
   *   The  project id returned from addProject().
   * @param string $notification_url
   *   The URL to which Lionbirdge will POST a project complete notification.
   *
   * @return mixed
   *   The quote data from Lionbridge.
   */
  public function generateQuote($project_id, $notification_url) {
    $quote_xml = [
      '#theme' => 'lionbridge_generate_quote',
      '#currency' => $this->getCurrency(),
      '#notification_url' => $notification_url,
      '#project_id' => $project_id,
    ];

    $result = $this->sendResource(
      '/api/quote/generate',
      \Drupal::service('renderer')->render($quote_xml),
      ['Content-type' => 'text/xml']
    );

    return $result;
  }

  /**
   * Sends a GET request to Lionbridge services.
   *
   * @param string $resource
   *   The path to send the request.
   *
   * @return mixed
   *   Either text if it's a translation or and array with requested data.
   */
  protected function getResource($resource) {
    $result = [];

    try {
      $response = $this->client->request(
        'GET',
        $this->endPoint . $resource,
        ['headers' => $this->generateAuthHeaders($resource)]
      );

      $xml_data = (string) $response->getBody();
      @$xml_obj = simplexml_load_string($xml_data);

      // If the generation of XML failed then it means that the response isn't
      // XML. Simply return the body instead.
      if (!$xml_obj) {
        return $xml_data;
      }

      $result = $this->xmlToArray($xml_obj);
    }
    catch (ClientException $e) {
      \Drupal::logger('lionbridge_translation_provider')->error($e->getMessage());
      $xml_data = (string) $e->getResponse()->getBody();
      @$xml_obj = simplexml_load_string($xml_data);
      $result = $this->xmlToArray($xml_obj);
    }

    return $result;
  }

  /**
   * Sends a POST request to Lionbridge.
   *
   * @param string $resource
   *   The path to send the request.
   * @param string $data
   *   Data to be sent to Lionbridge.
   * @param array $additional_headers
   *   Any additional request headers to be sent.
   *
   * @return mixed
   *   The XML data returned from Lionbridge converted to an array.
   *
   * @throws TMGMTException
   */
  protected function sendResource($resource, $data = '', array $additional_headers = []) {
    $result = [];

    try {
      $headers = $this->generateAuthHeaders($resource, 'POST') + $additional_headers;

      $response = $this->client->request(
        'POST',
        $this->endPoint . $resource,
        [
          'headers' => $headers,
          'body' => $data,
        ]
      );

      $xml_data = (string) $response->getBody();
      @$xml_obj = simplexml_load_string($xml_data);
      $result = $this->xmlToArray($xml_obj);
    }
    catch (ClientException $e) {
      \Drupal::logger('lionbridge_translation_provider')->error($e->getMessage());
      $error = str_replace('Client error: ', '', $e->getMessage());
      throw new TMGMTException($error);
    }

    return $result;
  }

  /**
   * Converts XML to an array.
   *
   * @param \SimpleXMLElement $xml_object
   *   The XML returned from Lionbridge.
   *
   * @return mixed
   *   An array of data converted from the XML object.
   */
  protected function xmlToArray(\SimpleXMLElement $xml_object) {
    return Json::decode(Json::encode((array) $xml_object));
  }

  /**
   * Generates the authorization header required by Lionbridge.
   *
   * @param string $resource
   *   The path to which the request will be sent.
   *
   * @return array
   *   An array of authorization headers.
   */
  public function generateAuthHeaders($resource, $method = 'GET', $micro_time = NULL) {
    date_default_timezone_set('America/New_York');
    if (is_null($micro_time)) {
      $micro_time = microtime(TRUE);
    }
    $micro = sprintf("%06d", ($micro_time - floor($micro_time)) * 1000000);
    $d = new \DateTime(date('Y-m-d H:i:s.' . $micro, $micro_time));
    $date_time = $d->format('Y-m-d\TH:i:s.u');

    $signature = "{$method}:{$resource}:{$this->secretId}:{$date_time}:{$this->apiVersion}:text/xml";
    $signature = base64_encode(hash('sha256', $signature, TRUE));
    $authorization = "LOD1-BASE64-SHA256 KeyID={$this->keyId},Signature={$signature},SignedHeaders=x-lod-timestamp;x-lod-version;accept";

    return [
      "Accept" => "text/xml",
      "Authorization" => $authorization,
      "x-lod-version" => $this->apiVersion,
      "x-lod-timestamp" => $date_time,
    ];
  }

}
