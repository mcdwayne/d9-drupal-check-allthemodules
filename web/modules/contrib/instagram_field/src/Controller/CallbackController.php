<?php

namespace Drupal\instagram_field\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;

/**
 * Instagram Field Callback Controller.
 */
class CallbackController extends ControllerBase {
  /**
   * The variable containing the conditions configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The variable containing the request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The variable containing the logging.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  private $logger;

  /**
   * The variable containing the http client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * Dependency injection through the constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \GuzzleHttp\Client $httpClient
   *   The http client service.
   */
  public function __construct(ConfigFactoryInterface $config,
  RequestStack $requestStack,
  LoggerChannelFactoryInterface $logger,
  Client $httpClient
  ) {
    $this->config = $config->getEditable('config.instagram_field');
    $this->requestStack = $requestStack->getCurrentRequest();
    $this->logger = $logger;
    $this->httpClient = $httpClient;
  }

  /**
   * Dependency injection create.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'),
    $container->get('request_stack'),
    $container->get('logger.factory'),
    $container->get('http_client'));
  }

  /**
   * Callback from instagram auth with accesstoken.
   */
  public function callback() {
    if ($this->requestStack->query->get('code') === NULL) {
      $err_msg = $this->t("instagramautherror: Error no code");
      $this->logger->get('instagram_field')->error($err_msg);
      drupal_set_message($err_msg, 'error');
      return [
        '#type' => 'markup',
        '#markup' => '',
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
    elseif (substr($this->requestStack->query->get('code'), 0, 5) == 'error') {
      $err_msg = $this->t("instagramautherror: @code", [
        '@code' => $this->requestStack->query->get('code'),
      ]);
      $this->logger->get('instagram_field')->error($err_msg);
      drupal_set_message($err_msg, 'error');
      return [
        '#type' => 'markup',
        '#markup' => '',
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
    else {
      $request = $this->httpPost('https://api.instagram.com/oauth/access_token', [
        'form_params' => [
          'client_id' => trim($this->config->get('clientid')),
          'client_secret' => trim($this->config->get('clientsecret')),
          'grant_type' => 'authorization_code',
          'redirect_uri' => $this->requestStack->getSchemeAndHttpHost() .
          '/_instagram_field_callback',
          'code' => preg_replace('/[^A-Fa-f0-9]/', '', $this->requestStack->query->get('code')),
        ],
      ]);
      $result = json_decode($request->getBody());
      $this->config->set('accesstoken', preg_replace('/[^A-Fa-f0-9.]/', '', $result->access_token));
      $this->config->save();
    }

    $response = new RedirectResponse('/admin/config/services/instagram_field');
    $response->send();
    return $response;
  }

  /**
   * HttpClient post method enable test mock.
   */
  private function httpPost($uri, $options) {
    if (drupal_valid_test_ua()) {
      return new Response(200, [], file_get_contents(dirname(__FILE__) . '/../../tests/src/Functional/Mocks/instagram_oauth_access_token.json'));
    }
    else {
      return $this->httpClient->post($uri, $options);
    }
  }

}
