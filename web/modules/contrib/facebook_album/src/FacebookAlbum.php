<?php
/**
 * @file
 * Contains \Drupal\facebook_album\FacebookAlbum.
 */

namespace Drupal\facebook_album;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;


/**
 * Facebook Album.
 */
class FacebookAlbum implements FacebookAlbumInterface {
  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a location form object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param ConfigFactoryInterface $config_factory
   */
  public function __construct(ClientInterface $http_client, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('config.factory')
    );
  }


  /**
   * Make a request to the specified url and return
   * a converted response
   *
   * @param string $call_path
   *    An extra path to be appended to the api's base url i.e. (albums/)
   * @param array $parameters
   *    Query string parameters to be appended to the api's base url
   * @return mixed
   *    A response rendered as an array
   */
  public function get($call_path = '', $parameters = array()) {
    $config = $this->configFactory->get('facebook_album.settings')->get();

    // Add our access token
    if (!empty($config['access_token'])) {
      $parameters['access_token'] = $config['access_token'];
    }

    $content_type = [0 => 'text/json'];
    $query = http_build_query($parameters);
    $url = FACEBOOK_ALBUM_API_BASE_URL . $call_path . '?' . $query;

    // Attempt to make the request, log and return otherwise
    try {
      $response = $this->httpClient->get($url);
      $content_type = $response->getHeader('Content-Type');
      $data = $response->getBody();
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      $data = $response->getBody()->getContents();
      $this->logger->warning('BadResponseException - Failed to get data "%error".', array('%error' => $e->getMessage()));

    }
    catch (RequestException $e) {
      $response = $e->getResponse();
      $data = $response->getBody()->getContents();
      $this->logger->warning('RequestException - Failed to get data "%error".', array('%error' => $e->getMessage()));

    }
    catch (\Exception $e) {
      $response = $e->getResponse();
      $data = $response->getBody()->getContents();

      $this->logger->warning('Exception - Failed to get data "%error".', array('%error' => $e->getMessage()));
    }

    return $this->response_to_array($content_type, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function translate_error($code, $message) {
    switch ($code) {
      case 1:
        $message = "Unable to retrieve data from Facebook. This could be due to an invalid Application ID/Application Secret or Facebook is temporarily unreachable.";
        break;
      case 10000:
        $message = "Unable to verify data from Facebook at this time. Please try again.";
        break;
    }

    return $message;
  }

  /**
   * Convert the API response into an array based on the content type
   * Currently only, json and plain-text responses are supported
   *
   * @param $content_type
   *    The type of content returned in the response. I.e. (json, plain-text, html)
   * @param $response
   *    The actual response to convert
   * @return mixed
   *    A response converted to an array
   */
  protected function response_to_array($content_type, $response) {
    if (strpos($content_type[0], "text/plain") !== FALSE) {
      $array = [];
      $a = explode(',', $response);

      foreach ($a as $response) {
        $b = explode('=', $response);
        $array[$b[0]] = $b[1];
      }

      $response = $array;
    }
    else if (!$response = Json::decode($response)) {
      $response = [];
      $response['error']['message'] = t("Unrecognized response type. Unable to parse data.");
      $response['error']['code'] = 10000;
    }

    if (!isset($response['data'])) {
      $response['data'] = [];
    }

    return $response;
  }


}
