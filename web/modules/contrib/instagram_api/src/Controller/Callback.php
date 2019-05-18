<?php

namespace Drupal\instagram_api\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Instagram Callback Controller.
 *
 * @package Drupal\instagram_api\Controller
 */
class Callback extends ControllerBase {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Callback Controller constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   An instance of ConfigFactory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerChannelFactoryInterface.
   */
  public function __construct(ConfigFactory $config,
                              LoggerChannelFactoryInterface $loggerFactory) {
    $this->config = $config->get('instagram_api.settings');
    $this->configEditable = $config->getEditable('instagram_api.settings');
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Callback URL for Instagram Auth.
   */
  public function callbackUrl(Request $request) {
    // TODO
    // Add a secure hash param to the previous request
    // And validate on return if this hash is valid.
    $code = $request->get('code');

    // Try to get the token.
    $token = $this->getToken($code);

    // If token is not empty.
    if ($token != FALSE) {
      // Save the token.
      $this->configEditable->set('access_token', $token)->save();
      $markup = $this->t("Access token saved");
    }
    else {
      $markup = $this->t("Failed to get access token. Check log messages.");
    }

    return ['#markup' => $markup];
  }

  /**
   * Fetch Instagram Token.
   */
  public function getToken($code) {
    // Guzzle Client.
    $guzzleClient = new GuzzleClient([
      'base_uri' => $this->config->get('api_uri'),
    ]);

    // Params.
    $parameters = [
      'client_id' => $this->config->get('client_id'),
      'client_secret' => $this->config->get('client_secret'),
      'redirect_uri' => Url::fromUri('internal:/instagram_api/callback', ['absolute' => TRUE])->toString(),
      'grant_type' => 'authorization_code',
      'code' => $code,
    ];

    try {
      $response = $guzzleClient->request(
        'POST',
        'access_token',
        ['form_params' => $parameters]
      );

      if ($response->getStatusCode() == 200) {
        // TODO Add debugging options.
        // kint($response->getBody()->getContents());
        $contents = $response->getBody()->getContents();
        return Json::decode($contents)['access_token'];
      }
    }
    catch (GuzzleException $e) {
      // TODO Add debugging options.
      // kint($e);
      $this->loggerFactory->get('instagram_api')->error("@message", ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

}
