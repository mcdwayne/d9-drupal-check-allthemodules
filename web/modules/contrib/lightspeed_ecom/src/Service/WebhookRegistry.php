<?php

namespace Drupal\lightspeed_ecom\Service;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\lightspeed_ecom\ShopDisabledException;
use Drupal\lightspeed_ecom\ShopInterface;
use Drupal\lightspeed_ecom\ShopNotDefinedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class WebhookRegistry.
 *
 * @package Drupal\lightspeed_ecom
 */
class WebhookRegistry implements WebhookRegistryInterface {

  /**
   * Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher definition.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;
  /**
   * Drupal\lightspeed_ecom\ApiClientFactory definition.
   *
   * @var \Drupal\lightspeed_ecom\Service\ApiClientFactoryInterface
   */
  protected $lightspeedEcomClientFactory;

  /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface  */
  protected $logger;

  /** @var \Drupal\lightspeed_ecom\Service\SecurityTokenGeneratorInterface  */
  protected $token;

  /** @var \Drupal\Core\Cache\CacheBackendInterface  */
  protected $cache;

  protected $url_generator;

  /**
   * Constructor.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, ApiClientFactoryInterface $lightspeed_ecom_client_factory, LoggerInterface $logger, SecurityTokenGeneratorInterface $token, CacheBackendInterface $cache, UrlGeneratorInterface $url_generator) {
    $this->eventDispatcher = $event_dispatcher;
    $this->lightspeedEcomClientFactory = $lightspeed_ecom_client_factory;
    $this->logger = $logger;
    $this->token = $token;
    $this->cache = $cache;
    $this->url_generator = $url_generator;
  }

  /**
   * Retrieve the list of registered webhooks for a given shop.
   *
   * @param $shop
   *   The shop to retrieve the registered webhooks for.
   * @param bool $reset
   *   Whether or not to reset the cache (defaults to FALSE).
   *
   * @return array|
   *   The list of webhook registered on Lightspeed eCom, indexed by event name.
   */
  protected function getRegisteredWebhooks($shop, $reset = FALSE) {
    $registered_webhooks = [];
    if (!$reset && $cache = $this->cache->get(__METHOD__)) {
      $registered_webhooks = $cache->data;
    }
    else {
      try {
        $api_response = $this->lightspeedEcomClientFactory->getClient($shop)->webhooks->get();
        $base_url = $this->url_generator->generateFromRoute('<front>', [], ['absolute' => TRUE]);
        foreach ($api_response as $webhook) {
          // Only keep our own webhooks (ie. those whose address starts with our base URL.)
          if (strpos($webhook['address'], $base_url) === 0) {
            $event_name = WebhookEvent::EVENT_NAMESPACE . ".{$webhook['itemGroup']}.{$webhook['itemAction']}";
            $registered_webhooks[$event_name] = $webhook;
          }
        }
        $this->cache->set(__METHOD__, $registered_webhooks);
      }
      catch (\Exception $exception) {
        $error = Error::decodeException($exception);
        $this->logger->log($error['severity_level'], 'Cannot retrieve webhooks: %type @message in %function (line %line of %file).', $error);
      }
    }
    return $registered_webhooks;
  }

  /**
   * Retrieve the webhook for the given shop.
   *
   * @param $shop
   *   The shop to retrieve the webhook for.
   * @param $group
   *   The group of the Webhook, one of 'customers', 'orders', 'invoices',
   *   'shipments', 'products', 'variants', 'quotes', 'reviews', 'returns',
   *   'tickets', 'subscriptions' and 'contacts'.
   * @param $action
   *   The action of the Webhook, one of 'created', 'updated', 'deleted', 'paid'
   *   (only for orders), 'shipped' (only for orders) and 'answered' (only for
   *   tickets)
   *
   * @return \Drupal\lightspeed_ecom\Service\Webhook|null
   */
  public function getWebhook($shop, $group, $action) {
    $event_name = WebhookEvent::EVENT_NAMESPACE . ".$group.$action";
    $listeners = $this->eventDispatcher->getListeners($event_name);
    if ($listeners) {
      return $this->getWebhookForEventListeners($shop, $event_name, $listeners);
    }
    return NULL;
  }

  /**
   * Return the webhook for a event and its listeners.
   *
   * @param ShopInterface $shop
   *   The shop to retrieve the webhook for.
   * @param string $event_name
   *   The name of the the event to retrieve the webhook for.
   * @param array $listeners
   *   An array of listeners, as returned by \Symfony\Component\EventDispatcher\EventDispatcherInterface::getListeners().
   *
   * @return \Drupal\lightspeed_ecom\Service\Webhook
   */
  protected function getWebhookForEventListeners(ShopInterface $shop, $event_name, $listeners) {
    $registered_webhooks = $this->getRegisteredWebhooks($shop);
    list(,$group, $action) = explode('.', $event_name, 3);
    // Map callable listeners to their string representations.
    $listeners = array_map(function ($listener) {
      if (is_array($listener) && is_object($listener[0]) && isset($listener[0]->_serviceId)) {
        // The listener is an object's method and the object is a service.
        // Use the service's ID as string representation.
        return '@' . $listener[0]->_serviceId;
      }
      else if (is_callable($listener, true, $callable_name)) {
        // Use the callable name as string representation.
        return $callable_name;
      }
    }, $listeners);
    if (isset($registered_webhooks[$event_name])) {
      // A webhook is registered on Lightspeed.
      $status = $registered_webhooks[$event_name]['isActive'] ? Webhook::STATUS_ACTIVE : Webhook::STATUS_INACTIVE;
      $id = $registered_webhooks[$event_name]['id'];
    }
    else {
      // The webhook is not yet registered on Lightspeed.
      $status = Webhook::STATUS_UNREGISTERED;
      $id = NULL;
    }
    $address = $this->getCallbackUrl($shop);
    return new Webhook($group, $action, $listeners, $address, $status, $id);
  }

  protected function getCallbackUrl(ShopInterface $shop) {
    static $urls = [];
    if (empty($urls[$shop->id()])) {
      $urls[$shop->id()] = $this->url_generator->generateFromRoute('lightspeed_ecom.webhook_receive', [
        'shop' => $shop->id(),
      ], [
        'absolute' => TRUE,
        'query' => [
          'token' => $this->token->get($shop),
        ]
      ]);
    }
    return $urls[$shop->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhooks(ShopInterface $shop) {
    // Retrieve the list of listeners for our events.
    $listeners_by_event_names = array_filter($this->eventDispatcher->getListeners(), function ($event_name) {
      return (strpos($event_name, WebhookEvent::EVENT_NAMESPACE . '.') === 0) && (substr_count($event_name, '.') === 2);
    }, ARRAY_FILTER_USE_KEY);

    // Map each lists of listeners to a Webhook.
    $webhooks = [];
    foreach ($listeners_by_event_names as $event_name => $listeners) {
      $webhooks[$event_name] = $this->getWebhookForEventListeners($shop, $event_name, $listeners);
    }
    return $webhooks;
  }

  /**
   * {@inheritdoc}
   */
  public function dispatch(WebhookEvent $event) {
    $event_name = implode('.', [WebhookEvent::EVENT_NAMESPACE, $event->getGroup(), $event->getAction()]);
    $this->eventDispatcher->dispatch($event_name, $event);
    return $event;
  }

  /**
   * {@inheritdoc}
   */
  public function synchronize(ShopInterface $shop) {
    $client = $this->lightspeedEcomClientFactory->getClient($shop);

    $registered_webhooks = $this->getRegisteredWebhooks($shop);
    foreach ($this->getWebhooks($shop) as $webhook) {

      $event_name = WebhookEvent::EVENT_NAMESPACE . ".{$webhook->getGroup()}.{$webhook->getAction()}";
      $fields = [
        'isActive' => TRUE,
        'itemGroup' => $webhook->getGroup(),
        'itemAction' => $webhook->getAction(),
        'language' => $shop->language()->getId(),
        'format' => 'json',
        'address' => $webhook->getAddress()
      ];
      if ($webhook->getStatus() == Webhook::STATUS_UNREGISTERED) {
        // Create missing webhook
        $client->webhooks->create($fields);
      }
      else {
        $registered_webhooks[$event_name]['language'] = $registered_webhooks[$event_name]['language']['code'];
        $updated_fields = array_diff_assoc($fields, $registered_webhooks[$event_name]);
        if ($updated_fields) {
          // update existing but different webhook.
          $client->webhooks->update($webhook->getId(), $updated_fields);
        }
      }
      unset($registered_webhooks[$event_name]);
    }
    // Remove un-needed webhooks
    foreach ($registered_webhooks as $registered_webhooks) {
      $client->webhooks->delete($registered_webhooks['id']);
    }
  }
}
