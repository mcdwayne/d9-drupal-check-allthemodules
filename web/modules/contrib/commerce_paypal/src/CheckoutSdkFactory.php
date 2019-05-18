<?php

namespace Drupal\commerce_paypal;

use Drupal\commerce_order\AdjustmentTransformerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\HandlerStack;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a factory for our custom PayPal checkout SDK.
 */
class CheckoutSdkFactory implements CheckoutSdkFactoryInterface {

  /**
   * The core client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * The handler stack.
   *
   * @var \GuzzleHttp\HandlerStack
   */
  protected $stack;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The adjustment transformer.
   *
   * @var \Drupal\commerce_order\AdjustmentTransformerInterface
   */
  protected $adjustmentTransformer;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Array of all instantiated PayPal Checkout SDKs.
   *
   * @var \Drupal\commerce_paypal\CheckoutSdkInterface[]
   */
  protected $instances = [];

  /**
   * Constructs a new CheckoutSdkFactory object.
   *
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   *   The client factory.
   * @param \GuzzleHttp\HandlerStack $stack
   *   The handler stack.
   * @param \Drupal\commerce_order\AdjustmentTransformerInterface $adjustment_transformer
   *   The adjustment transformer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ClientFactory $client_factory, HandlerStack $stack, AdjustmentTransformerInterface $adjustment_transformer, EventDispatcherInterface $event_dispatcher, ModuleHandlerInterface $module_handler, StateInterface $state) {
    $this->clientFactory = $client_factory;
    $this->stack = $stack;
    $this->adjustmentTransformer = $adjustment_transformer;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function get(array $configuration) {
    $client_id = $configuration['client_id'];
    if (!isset($this->instances[$client_id])) {
      $client = $this->getClient($configuration);
      $this->instances[$client_id] = new CheckoutSdk($client, $this->adjustmentTransformer, $this->eventDispatcher, $this->moduleHandler, $configuration);
    }

    return $this->instances[$client_id];
  }

  /**
   * Gets a preconfigured HTTP client instance for use by the SDK.
   *
   * @param array $config
   *   The config for the client.
   *
   * @return \GuzzleHttp\Client
   *   The API client.
   */
  protected function getClient(array $config) {
    switch ($config['mode']) {
      case 'live':
        $base_uri = 'https://api.paypal.com';
        break;

      case 'test':
      default:
        $base_uri = 'https://api.sandbox.paypal.com';
        break;
    }
    $options = [
      'base_uri' => $base_uri,
      'headers' => [
        'PayPal-Partner-Attribution-Id' => 'CommerceGuys_Cart_SPB',
      ],
    ];
    $client = $this->clientFactory->fromOptions($options);
    $config = [
      ClientCredentials::CONFIG_CLIENT_ID => $config['client_id'],
      ClientCredentials::CONFIG_CLIENT_SECRET => $config['secret'],
      ClientCredentials::CONFIG_TOKEN_URL => '/v1/oauth2/token',
    ];
    $grant_type = new ClientCredentials($client, $config);
    $middleware = new OAuthMiddleware($client, $grant_type);
    // Check if we've already requested an oauth2 token, note that we do not
    // need to check for the expires timestamp here as the middleware is already
    // taking care of that.
    // @todo: This should support multiple tokens.
    $token = $this->state->get('commerce_paypal.oauth2_token');
    if (!empty($token)) {
      $middleware->setAccessToken($token['token'], 'client_credentials', $token['expires']);
    }
    $this->stack->push($middleware->onBefore());
    $this->stack->push($middleware->onFailure(2));
    $options['handler'] = $this->stack;
    $options['auth'] = 'oauth2';
    return $this->clientFactory->fromOptions($options);
  }

}
