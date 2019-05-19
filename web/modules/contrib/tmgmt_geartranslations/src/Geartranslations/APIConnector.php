<?php

namespace Drupal\tmgmt_geartranslations\Geartranslations;

use Drupal\Core\Url;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_geartranslations\Geartranslations\APIException;

/**
 * Class used to make calls to the GearTranslations API
 *
 * @requires curl
 * @requires APIException
 */
class APIConnector {
  /**
   * GearTranslations API connection timeout, in seconds
   * @var integer
   */
  const CONNECTION_TIMEOUT = 30;

  /**
   * GearTranslations API endpoint
   * @var string
   */
  private $endpoint;

  /**
   * GearTranslations API access token
   * @var string
   */
  private $token;

  /**
   * Geartranslations Exception contructor
   *
   * @param many $translator Translator interface.
   * @return boolean TRUE if the API is configured; FALSE otherwise 
   */
  public static function isConfigured($translator) {
    return $translator->getSetting('endpoint') && $translator->getSetting('token');
  }

  /**
   * Geartranslations Exception contructor
   *
   * @param many $translator Translator interface.
   * @return APIConnector Creates an API instance.
   */
  public static function build($translator) {
    if (self::isConfigured($translator)) {
      return new self($translator->getSetting('endpoint'), $translator->getSetting('token'));
    }
    else {
      throw new APIException('API is not configured');
    }
  }

  /**
   * APIConnector constructor
   *
   * @param string $enpoint GearTranslations API url (http).
   * @param string $token GearTranslations API access token.
   * */
  public function __construct($endpoint, $token) {
    $this->endpoint = $endpoint;
    $this->token = $token;
  }

  /**
   * Calls the GearTranslations API to check if the service is available.
   *
   * @throws APIException Raises an exception if the service is not available or not configured.
   */
  public function ping() {
    return $this->get('/ping');
  }

  /**
   * Calls the GearTranslations API to retrieve the translation packages.
   *
   * @return array hash with the package ids and names, with the format id => name
   */
  public function getTranslationPackages($source, $target) {
    $output = [];
    $params = ['language_from_code' => $source, 'language_to_code' => $target];

    $packages = $this->get('/packages', $params)['packages'];
    $default_package = '';

    foreach ($packages as $package) {
      $output[$package['code']] = $package['name'];

      if ($package['default']) {
        $default_package = $package['code'];
      }
    }

    return ['packages' => $output, 'default' => $default_package];
  }

  /**
   * Calls the GearTranslations API to retrieve the available languages, using remote codes.
   *
   * @return array list of available language codes.
   */
  public function getLanguages() {
    $response = $this->get('/languages');
    return $response['languages'];
  }

  /**
   * Calls the GearTranslations API to retrieve the target languages for a source language,
   * using remote codes.
   *
   * @param string $source Source remove language code.
   * @return array list of available target languages codes.
   */
  public function getTargetLanguages($source) {
    $response = $this->get('/languages', ['language_from_code' => $source]);
    return $response['languages'];
  }

  /**
   * Notify GearTranslations about a job deletion.
   *
   * @param JobInterface $job Job that is being deleted.
   */
  public function notifyJobDeletion(JobInterface $job) {
    $this->delete("/translation_requests/{$job->id()}/force_cancel");
  }

  /**
   * Request a translation.
   *
   * @param JobInterface $job Job that is being requested.
   */
  public function requestTranslation(JobInterface $job) {
    $payload = $this->buildTranslationRequestPayload($job);
    $this->post('/translation_requests', $payload);
  }

  /**
   * Try to cancel a remote job.
   *
   * @param JobInterface $job Job that wants to be cancelled.
   */
  public function abortTranslationRequest(JobInterface $job) {
    return $this->delete("/translation_requests/{$job->id()}");
  }

  public function profileLinks() {
    $userId = $this->ping()['user_id'];

    $urlParts = parse_url($this->endpoint);
    $baseUrl = $urlParts['scheme'] . '://' . $urlParts['host'];
    if (array_key_exists('port', $urlParts) && $urlParts['port']) {
      $baseUrl .= ':' . $urlParts['port'];
    }

    return [
      '@monthly_expenses' => $baseUrl . "/clients/$userId/monthly_expenses",
      '@translation_library' => $baseUrl . "/clients/$userId/translation_library",
    ];
  }

  /**
   * Perform a GET request.
   *
   * @param string $path API path or action to call.
   * @param array $params Hash of query parameters, with the form param => value.
   * @return array JSON server response.
   */
  private function get($path, $params = []) {
    return $this->request($path, 'GET', $params, NULL);
  }

  /**
   * Perform a POST request.
   *
   * @param string $path API path or action to call.
   * @param array $params Hash of post body parameters, with the form param => value.
   * @return array JSON server response.
   */
  private function post($path, $params = []) {
    return $this->request($path, 'POST', NULL, $params);
  }

    /**
   * Perform a DELETE request.
   *
   * @param string $path API path or action to call.
   * @param array $params Hash of query parameters, with the form param => value.
   * @return array JSON server response.
   */
  private function delete($path, $params = []) {
    return $this->request($path, 'DELETE', $params, NULL);
  }

  /**
   * Perform an HTTP request.
   *
   * @param string $path API path or action to call.
   * @param string $method API method to be called (GET, POST, PUT...).
   * @param array $queryParams Hash of post body parameters, with the form param => value.
   * @param array $postParams Hash of post body parameters, with the form param => value.
   * @return array JSON server response.
   */
  private function request($path, $method, $queryParams, $postParams) {
    # Build query parameters
    $parameters = '';
    if ($queryParams && is_array($queryParams)) {
      foreach ($queryParams as $param => $value) {
        $separator = empty($parameters) ? '?' : '&';
        $parameters .= $separator . $param . '=' . urlencode($value);
      }
    }

    # Build url
    $url = rtrim($this->endpoint, '/') . '/' . ltrim($path, '/') . '.json' . $parameters;

    # Build curl client
    $curl = curl_init();
    $headers = ['Access-Token: ' . $this->token];
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CONNECTION_TIMEOUT);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    # Build post params (JSON content)
    if ($postParams && is_array($postParams)) {
      $headers[] = 'Content-Type: application/json';
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postParams));
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    # Execute curl request
    $content = curl_exec($curl);
    $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    # Return parsed response, if valid
    if ($response_code === 200 || $response_code === 201) {
      return json_decode($content, TRUE);
    }

    # Raise an exception otherwise
    switch ($response_code) {
      case 401:
        throw new APIException('Service usage is not authorized or is not properly configured');
      case 400:
        $json = json_decode($content, TRUE);
        throw new APIException($json['message']);
      case 503:
        throw new APIException('Service under maintenance');
      default:
        throw new APIException("Unknown service error ($response_code); please, try again later");
    }
  }

   /**
   * Create the required payload to validate a translation request.
   * @param JobInterface $job Job to extract attributes from.
   */
  private function buildBasicTranslationRequestPayload(JobInterface $job) {
    return [
      'job_id' => $job->id(),
      'callback' => Url::fromRoute('tmgmt_geartranslations.callback')->setAbsolute()->toString(),
      'language_from_code' => $job->getRemoteSourceLanguage(),
      'language_to_code' => $job->getRemoteTargetLanguage(),
      'package' => $job->getSetting('package')
    ];
  }

  /**
   * Create the required payload to send to the translation service as a translation request.
   * @param JobInterface $job Job to extract attributes from.
   */
  private function buildTranslationRequestPayload(JobInterface $job) {
    $payload = $this->buildBasicTranslationRequestPayload($job);
    $payload['texts'] = [];

    // Register each job item on our payload
    foreach ($job->getItems() as $job_item) {
      $payload['texts'][$job_item->id()] = [];

      // Pull the source data array through the job and flatten it
      $strings = \Drupal::service('tmgmt.data')->filterTranslatable($job_item->getData());

      // Note that each string may contain multiple strings
      foreach ($strings as $key => $string) {
        // Select only those strings that should be translated
        if ($string['#translate']) {
          $payload['texts'][$job_item->id()][$key] = [
            '#text' => $string['#text'],
            '_comment' => $job_item->getSourceLabel()
          ];
        }
      }
    }

    return $payload;
  }
}
