<?php

namespace Drupal\peytz_mail;

use Drupal\Core\Config\ConfigFactoryInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class which can communicate via Peytz Mail's webservice API.
 */
class PeytzMailer {

  /**
   * API key used for authentication.
   *
   * @var string
   */
  private $apiKey;

  /**
   * Service domain.
   *
   * @var string
   */
  private $serviceUrl;

  /**
   * CURL handle.
   *
   * @var resource
   */
  private $curlHandle;

  /**
   * Additional options.
   *
   * @var array
   */
  private $options = [];

  /**
   * Service operation response code.
   *
   * @var string
   */
  private $responseCode;

  /**
   * Service operation response body.
   *
   * @var string
   */
  private $responseBody;

  /**
   * Request debug information.
   *
   * @var string
   */
  private $requestDetails;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Instantiates mailer by setting credentials for HTTP basic authentication.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory Interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $peytz_mail_settings = $this->configFactory->get('peytz_mail.settings');
    $this->serviceUrl = $peytz_mail_settings->get('service_url');
    $this->apiKey = $peytz_mail_settings->get('api_key');

    $this->options = [
      'debug'   => FALSE,
      'timeout' => 30,
    ];
  }

  /**
   * Instantiates a new instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Symfony Dependency Injection Container Interface.
   *
   * @return static
   *   New instance of this class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Set Peytz mail settings.
   *
   * @param string $service_url
   *   Service URl.
   * @param string $api_key
   *   API key.
   */
  public function setSettings($service_url = '', $api_key = '') {
    if (!empty($service_url)) {
      $this->serviceUrl = $service_url;
    }
    if (!empty($api_key)) {
      $this->apiKey = $api_key;
    }
  }

  /**
   * Check settings of mailer.
   *
   * @return bool
   *   Are the settings valid?
   */
  public function isNotReadyForRequests() {
    return empty($this->serviceUrl) || empty($this->apiKey);
  }

  /**
   * Validate configuration setting by attempting an API call.
   *
   * @return bool|string
   *   True if peytzmail is correctly configured, error message otherwise.
   */
  public function checkSettings() {
    try {
      $response = $this->getMailingLists();
      if (!empty($response->error)) {
        return $response->error;
      }

      if ($this->responseCode != 200) {
        return "An error occurred. Code {$this->responseCode}, response: " . var_export($this->responseBody, TRUE) . ".";
      }
    }
    catch (Exception $e) {
      return $e->getMessage();
    }

    return TRUE;
  }

  /**
   * Check connection to service url.
   *
   * @param string $service_url
   *   Service url.
   *
   * @return bool|string
   *   True if api key has access to service_url, error message otherwise.
   */
  public function checkStatus($service_url) {

    $su = $this->serviceUrl;

    $url_parts = parse_url($service_url);
    $url_scheme = !empty($url_parts['scheme']) ? $url_parts['scheme'] : '';
    $host = !empty($url_parts['host']) ? $url_parts['host'] : '';
    $this->serviceUrl = $url_scheme . '://' . $host . '/api/v1/system/status.json';

    try {
      $response = $this->request();
      if (!empty($response->error)) {
        $result = "An error occurred. Code {$this->responseCode}, " . $response->error;
      }
      elseif ($this->responseCode != 200) {
        $result = "An error occurred. Code {$this->responseCode}, response: " . var_export($this->responseBody, TRUE);
      }
    }
    catch (Exception $e) {
      $result = $e->getMessage();
    }

    $this->serviceUrl = $su;

    return isset($result) ? $result : TRUE;
  }

  /**
   * Sets configuration options.
   *
   * @param array $options
   *   An array of options to set.
   */
  public function setOptions(array $options) {
    $this->options = array_merge($this->options, $options);
  }

  /**
   * Retrieves current configuration options.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Check for debug mode.
   *
   * @return bool
   *   Is debug mode enabled?
   */
  public function isDebugMode() {
    return $this->options['debug'];
  }

  /**
   * Performs a RESTful request (using cURL library).
   *
   * @param string $path
   *   The requested URL.
   * @param string $method
   *   Request method, defaults to 'GET'.
   * @param array $data
   *   Optional data.
   * @param string $headers
   *   Additional headers.
   * @param bool $async
   *   Weather the request should be made async or not.
   *
   * @throws Exception
   *   Exception.
   *
   * @return string
   *   Request response
   */
  private function request($path = '', $method = 'GET', array $data = [], $headers = NULL, $async = FALSE) {

    $mandatory_headers = [
      'Accept: application/json',
      'Content-Type: application/json',
    ];
    if ($headers && is_array($headers)) {
      $mandatory_headers = array_merge($mandatory_headers, $headers);
    }
    $json_data = json_encode($data);

    $this->curlHandle = curl_init(rtrim($this->serviceUrl, '/') . '/' . $path);
    curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $mandatory_headers);
    curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($this->curlHandle, CURLOPT_USERPWD, $this->apiKey . ':');

    if (!empty($this->options['debug'])) {
      curl_setopt($this->curlHandle, CURLOPT_VERBOSE, TRUE);
      $dbg = fopen('php://temp', 'r+');
      curl_setopt($this->curlHandle, CURLOPT_STDERR, $dbg);
    }

    if (!empty($this->options['timeout'])) {
      curl_setopt($this->curlHandle, CURLOPT_CONNECTTIMEOUT, $this->options['timeout']);
    }

    switch ($method) {
      case 'PUT':
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $json_data);
        if ($async) {
          curl_setopt($this->curlHandle, CURLOPT_FRESH_CONNECT, TRUE);
          curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, 1);
        }
        break;

      case 'DELETE':
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $json_data);
        break;

      case 'POST':
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $json_data);
        if ($async) {
          curl_setopt($this->curlHandle, CURLOPT_FRESH_CONNECT, TRUE);
          curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, 1);
        }
        break;

      // GET is the default method.
      default:
        curl_setopt($this->curlHandle, CURLOPT_HTTPGET, TRUE);
        break;
    }

    $response = curl_exec($this->curlHandle);

    if (!empty($this->options['debug'])) {
      rewind($dbg);
      $this->requestDetails = stream_get_contents($dbg);
      fclose($dbg);
    }

    if ($response === FALSE && !$async) {
      throw new Exception(curl_error($this->curlHandle));
    }

    $this->responseCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    $this->responseBody = json_decode($response);

    curl_close($this->curlHandle);
    $this->curlHandle = NULL;

    return $this->responseBody;
  }

  /**
   * Get the response code.
   */
  public function getResponseCode() {
    return $this->responseCode;
  }

  /**
   * Get the response body.
   */
  public function getResponseBody() {
    return $this->responseBody;
  }

  /**
   * Get additional request details.
   *
   * @param bool $secure
   *   Obscure API key in response or not.
   *
   * @return string
   *   Request details string.
   */
  public function getRequestDetails($secure = TRUE) {
    // Replace API with asterisks.
    if ($secure) {
      $this->requestDetails = str_replace($this->apiKey, '*****', $this->requestDetails);
    }
    return $this->requestDetails;
  }

  /**
   * Creates a new subscriber.
   *
   * @param array $data
   *   Subscriber details.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function createSubscriber(array $data) {
    if (empty($data) || empty($data['email'])) {
      throw new Exception("Subscriber's email is required.");
    }

    $path = 'subscribers.json';
    $data = ['subscriber' => $data];
    return $this->request($path, 'POST', $data);
  }

  /**
   * Subscriber find/get methods.
   *
   * @param string $id
   *   ID of a subscriber.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function getSubscriber($id) {
    if ($id) {
      $path = 'subscribers/' . $id . '.json';
    }
    else {
      throw new Exception("Subscriber ID is required.");
    }
    return $this->request($path);
  }

  /**
   * Get all available subscribers.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function getSubscribers() {
    $path = 'subscribers.json';
    return $this->request($path);
  }

  /**
   * Search for a subscriber given search parameters.
   *
   * @param array $parameters
   *   An array of criterion => value pairs.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function findSubscribers(array $parameters = []) {
    if (empty($parameters)) {
      throw new Exception("No search parameters supplied.");
    }

    $params = [];
    foreach ($parameters as $key => $value) {
      $params[] = "criteria[$key]=$value";
    }
    $path = 'subscribers/search.json?' . implode('&', $params);
    return $this->request($path);
  }

  /**
   * Create a new mailing list.
   *
   * @param array $data
   *   The data defining a mailinglist.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function createMailingList(array $data) {
    if (empty($data) || !is_array($data)) {
      throw new Exception("Required parameters: title, send_welcome_mail, send_confirmation_mail, default_template");
    }
    if (!isset($data['title'])) {
      throw new Exception("Mailinglist title is required.");
    }
    if (!isset($data['send_welcome_mail'])) {
      throw new Exception("Mailinglist parameter 'send_welcome_mail' is required.");
    }
    if (!isset($data['send_confirmation_mail'])) {
      throw new Exception("Mailinglist parameter 'send_confirmation_mail' is required.");
    }
    if (!isset($data['default_template'])) {
      throw new Exception("Mailinglist should have a 'default_template' specified.");
    }

    $path = 'mailinglists.json';
    $data = ['mailinglist' => $data];
    return $this->request($path, 'POST', $data);
  }

  /**
   * Adds a new subscriber to mailinglist(s).
   *
   * @param array $data
   *   An array filled with subscriber info.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function subscribe(array $data) {
    if (empty($data['subscriber']) || empty($data['subscriber']['email'])) {
      throw new Exception("Subscriber's email is required.");
    }

    if (empty($data['mailinglist_ids'])) {
      throw new Exception("Mailinglist IDs are required and cannot be empty.");
    }

    $path = 'mailinglists/subscribe.json';
    $data = ['subscribe' => $data];
    return $this->request($path, 'POST', $data);
  }

  /**
   * Unsubscribe subscriber from mailinglist(s).
   *
   * @param array $data
   *   Array filled with info subscriber info.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function unsubscribe(array $data) {
    if (empty($data['email'])) {
      throw new Exception("Unsubscribe email is required.");
    }

    $path = 'mailinglists/unsubscribe.json';
    $data = ['unsubscribe' => $data];
    return $this->request($path, 'POST', $data);
  }

  /**
   * Unsubscribe single subscriber by IF from single mailinglist.
   *
   * @param string $mailinglist_id
   *   Mailing list ID.
   * @param string $subscriber_id
   *   Subscriber ID.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function unsubscribeById($mailinglist_id, $subscriber_id) {
    if (empty($mailinglist_id)) {
      throw new Exception("Mailinglist ID is required and cannot be empty.");
    }

    if (empty($subscriber_id)) {
      throw new Exception("Subscriber ID is required and cannot be empty.");
    }

    $path = 'mailinglists/' . $mailinglist_id . '/subscribers/' . $subscriber_id . '.json';
    return $this->request($path, 'DELETE');
  }

  /**
   * Gets a mailinglist.
   *
   * @param string $mailinglist_id
   *   Mailinglist ID.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function getMailingList($mailinglist_id) {
    if (empty($mailinglist_id)) {
      throw new Exception("Mailinglist ID is required and cannot be empty.");
    }

    $path = 'mailinglists/' . $mailinglist_id . '.json';
    return $this->request($path);
  }

  /**
   * Gets all mailinglists.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function getMailingLists() {
    $path = 'mailinglists.json';
    return $this->request($path);
  }

  /**
   * Get newsletters of a given mailinglist.
   *
   * @param string $mailinglist_id
   *   Mailinglist ID.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function getNewsletters($mailinglist_id) {
    if (empty($mailinglist_id)) {
      throw new Exception("Mailinglist ID is required and cannot be empty.");
    }

    $path = 'mailinglists/' . $mailinglist_id . '/newsletters.json';
    return $this->request($path);
  }

  /**
   * Create newsletter and assign a mailinglist to it.
   *
   * @param string $mailinglist_id
   *   The ID of the mailinglist we operate on.
   * @param array $data
   *   Additional data defining a newsletter.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function createNewsletter($mailinglist_id, array $data) {
    if (empty($mailinglist_id)) {
      throw new Exception("Mailinglist ID is required and cannot be empty.");
    }

    if (empty($data) || empty($data['title']) || empty($data['template'])) {
      throw new Exception("Newsletter title and template are required.");
    }

    $path = 'mailinglists/' . $mailinglist_id . '/newsletters.json';
    $data = ['newsletter' => $data];
    return $this->request($path, 'POST', $data);
  }

  /**
   * Configure newsletter by setting its data feed source.
   *
   * @param string $mailinglist_id
   *   Mailinglist ID.
   * @param string $newsletter_id
   *   Newseletter ID.
   * @param array $data
   *   Updated configuration of a newsletter.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function configureNewsletter($mailinglist_id, $newsletter_id, array $data) {
    if (empty($mailinglist_id)) {
      throw new Exception("Mailinglist ID is required and cannot be empty.");
    }

    if (empty($data['feeds'])) {
      throw new Exception("Newsletter feed are required.");
    }

    foreach ($data['feeds'] as $feed) {
      if (empty($feed['name'])) {
        throw new Exception("Feed's name is required.");
      }
    }

    $path = 'mailinglists/' . $mailinglist_id . '/newsletters/' . $newsletter_id . '/push_data.json';
    $data = ['newsletter' => $data];
    return $this->request($path, 'POST', $data);
  }

  /**
   * Performs a test send of newsletter to a single email address.
   *
   * @param string $mailinglist_id
   *   Mailinglist ID.
   * @param string $newsletter_id
   *   Newsletter ID.
   * @param string $email
   *   Email address to send newsletter.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function testNewsletter($mailinglist_id, $newsletter_id, $email) {
    if (empty($mailinglist_id)) {
      throw new Exception("Mailinglist ID is required and cannot be empty.");
    }

    if (empty($newsletter_id)) {
      throw new Exception("Newsletter ID is required and cannot be empty.");
    }

    $path = 'mailinglists/' . $mailinglist_id . '/newsletters/' . $newsletter_id . '/test.json';
    $path .= '?email=' . $email;
    return $this->request($path);
  }

  /**
   * Sends a newsletter.
   *
   * @param string $mailinglist_id
   *   Mailinglist ID.
   * @param string $newsletter_id
   *   Newsletter ID.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function sendNewsletter($mailinglist_id, $newsletter_id) {
    if (empty($mailinglist_id)) {
      throw new Exception("Mailinglist ID is required and cannot be empty.");
    }

    if (empty($newsletter_id)) {
      throw new Exception("Newsletter ID is required and cannot be empty.");
    }

    $path = 'mailinglists/' . $mailinglist_id . '/newsletters/' . $newsletter_id . '/send.json';
    return $this->request($path);
  }

  /**
   * Gets newsletter details.
   *
   * Retrieve detailed information about newsletter including sending
   * statistics.
   *
   * @param string $mailinglist_id
   *   Mailinglist ID.
   * @param string $newsletter_id
   *   Newsletter ID.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function getNewsletterDetails($mailinglist_id, $newsletter_id) {
    if (empty($mailinglist_id)) {
      throw new Exception("Mailinglist ID is required and cannot be empty.");
    }

    if (empty($newsletter_id)) {
      throw new Exception("Newsletter ID is required and cannot be empty.");
    }

    $path = 'mailinglists/' . $mailinglist_id . '/newsletters/' . $newsletter_id . '.json';
    return $this->request($path);
  }

  /**
   * Trigger an email.
   *
   * @param array $data
   *   The array with trigger mail information.
   * @param bool $async
   *   Weather request should be made async or not.
   *
   * @return string
   *   Request response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function triggerMail(array $data, $async = FALSE) {
    if (empty($data)) {
      throw new Exception("Need trigger mail data to contain valid info.");
    }

    $path = 'trigger_mails.json';

    return $this->request($path, 'POST', ['trigger_mail' => $data], NULL, $async);
  }

}
