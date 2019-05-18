<?php

namespace Drupal\bcubed_google_analytics\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProxyController.
 *
 * @package Drupal\bcubed_google_analytics\Controller
 */
class ProxyController implements ContainerInjectionInterface {

  /**
   * GuzzleHttp Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Creates an instance of WorkflowsFieldContraintValidator.
   */
  public function __construct(Client $client, ConfigFactory $config_factory) {
    $this->client = $client;
    $this->config = $config_factory->get('google_analytics.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'), $container->get('config.factory'));
  }

  /**
   * Send event to google analytics.
   */
  public function sendEvent(Request $request) {
    // Set localhost as a trusted proxy.
    $request->setTrustedProxies(['127.0.0.1']);

    $ip = $request->getClientIp();
    $ua = $request->headers->get('user-agent');

    $query = http_build_query([
      'v' => '1',
      't' => 'event',
      'tid' => $this->config->get('account'),
      'cid' => substr(md5($ip . $ua), 0, 8),
      'uip' => $ip,
      'ua' => $ua,
    ]);

    $query .= '&' . $request->getQueryString();

    $url = 'https://www.google-analytics.com/collect?' . $query;

    $this->client->post($url, ['async' => TRUE]);

    return Response::create('true');
  }

}
