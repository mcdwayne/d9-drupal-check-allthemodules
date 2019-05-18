<?php

namespace Drupal\Tests\lightspeed_ecom\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\lightspeed_ecom\Entity\Shop;
use Drupal\lightspeed_ecom\Service\ApiClientFactoryInterface;
use Drupal\lightspeed_ecom\Service\SecurityTokenGeneratorInterface;
use Drupal\lightspeed_ecom\Service\Webhook;
use Drupal\lightspeed_ecom\Service\WebhookEvent;
use Drupal\lightspeed_ecom\Service\WebhookRegistry;
use \Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class WebhookTest
 *
 * @group Lightspeed eCom
 */
class WebhookRegistryTest extends UnitTestCase {

  /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface */
  protected $eventDispatcher;

  /** @var  \Drupal\lightspeed_ecom\Service\ApiClientFactoryInterface */
  protected $lightspeedEcomClientFactory;

  /** @var  \WebshopappApiClient */
  protected $lightspeedEcomClient;

  /** @var  EntityTypeManagerInterface */
  protected $entityTypeManager;

  /** @var  SecurityTokenGeneratorInterface */
  protected $token;

  /** @var  UrlGeneratorInterface */
  protected $url_generator;

  /** @var  LoggerInterface */
  protected $logger;

  /** @var  CacheBackendInterface */
  protected $cache;

  public function setUp() {
    parent::setUp();

    $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
    $this->lightspeedEcomClientFactory = $this->getMock(ApiClientFactoryInterface::class);
    $this->lightspeedEcomClient = $this->getMockBuilder(\WebshopappApiClient::class)
      ->setConstructorArgs(array("https://api.webshopapp.net/", "**key**", "**secret**", "en"))
      ->getMock();
    $this->lightspeedEcomClientFactory->expects($this->any())
      ->method('getClient')
      ->willReturn($this->lightspeedEcomClient );
    $this->logger = $this->getMock(LoggerInterface::class);
    $this->token = $this->getMock(SecurityTokenGeneratorInterface::class);
    $token = 'qwertyuiop';
    $this->token->expects($this->any())
      ->method('get')
      ->willReturn($token);
    $this->token->expects($this->any())
      ->method('validate')
      ->willReturnCallback(function ($value) use ($token) {
        return $value === $token;
      });
    $this->cache = new MemoryBackend('test');
    $this->url_generator = $this->getMock(UrlGeneratorInterface::class);
    $this->url_generator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturnCallback(function ($route, $parameters, $options) {
        $url = "http://example.com/";
        switch ($route) {
          case 'lightspeed_ecom.webhook_receive':
            $url .= "lightspeed_ecom/webhook/" . $parameters['shop'];
            break;
        }
        if (!empty($options['query'])) {
          $url .= "?" . http_build_query($options['query']);
        }
        return $url;
      });
  }

  /**
   * Test the WebhookRegistry::getWebhooks() method.
   */
  public function testGetWebhooks() {

    $listeners = [
      WebhookEvent::CUSTOMERS_UPDATED => [
        [(object) ['_serviceId' => 'foo'], 'bar'],
        [(object) ['_serviceId' => 'bar'], 'foo']
      ],
      WebhookEvent::CUSTOMERS_CREATED => [
        'someRandomFunctionName',
        array("SomeClass", "someMethod"),
        array(new \stdClass(), "someMethod"),
        [(object) ['_serviceId' => 'bar'], 'foo']
      ],
      WebhookEvent::CUSTOMERS_DELETED => [
        [(object) ['_serviceId' => 'foo'], 'foo']
      ]
    ];

    $registered_webhooks = [
      // The webhook for the lightspeed_ecom.customers.create event
      [
        "id" => 36,
        "createdAt" => "2013-09-17T23:34:46+02:00",
        "updatedAt" => "2013-09-17T23:35:29+02:00",
        "isActive" => FALSE,
        "itemGroup" => "customers",
        "itemAction" => "created",
        "language" => [
          "id" => 1,
          "code" => "nl",
          "locale" => "nl_NL",
          "title" => "Nederlands"
        ],
        "format" => "json",
        "address" => "http://example.com/lightspeed_ecom/webhook/default"
      ],
      // The webhook for the lightspeed_ecom.customers.update event
      [
        "id" => 37,
        "createdAt" => "2013-09-17T23:34:46+02:00",
        "updatedAt" => "2013-09-17T23:35:29+02:00",
        "isActive" => TRUE,
        "itemGroup" => "customers",
        "itemAction" => "updated",
        "language" => [
            "id" => 1,
          "code" => "nl",
          "locale" => "nl_NL",
          "title" => "Nederlands"
        ],
        "format" => "json",
        "address" => "http://example.com/lightspeed_ecom/webhook/default"
      ],
      // An non-supported webhook
      [
        "id" => 38,
        "createdAt" => "2013-09-17T23:34:46+02:00",
        "updatedAt" => "2013-09-17T23:35:29+02:00",
        "isActive" => TRUE,
        "itemGroup" => "customers",
        "itemAction" => "*",
        "language" => [
          "id" => 1,
          "code" => "nl",
          "locale" => "nl_NL",
          "title" => "Nederlands"
        ],
        "format" => "json",
        "address" => "http://example.com/lightspeed_ecom/webhook/default"
      ],
      // A supported but not used to webhook
      [
        "id" => 39,
        "createdAt" => "2013-09-17T23:34:46+02:00",
        "updatedAt" => "2013-09-17T23:35:29+02:00",
        "isActive" => TRUE,
        "itemGroup" => "customers",
        "itemAction" => "*",
        "language" => [
          "id" => 1,
          "code" => "nl",
          "locale" => "nl_NL",
          "title" => "Nederlands"
        ],
        "format" => "json",
        "address" => "http://example.com/lightspeed_ecom/webhook/default"
      ]
    ];

    $expected_webhooks = [
      WebhookEvent::CUSTOMERS_UPDATED => [
        'group' => 'customers',
        'action' => 'updated',
        'services' => array('@foo', '@bar'),
        'id' => '37',
        'status' => Webhook::STATUS_ACTIVE
      ],
      WebhookEvent::CUSTOMERS_CREATED => [
        'group' => 'customers',
        'action' => 'created',
        'services' => [
          'someRandomFunctionName',
          'SomeClass::someMethod',
          'stdClass::someMethod',
          '@bar'
        ],
        'id' => '36',
        'status' => Webhook::STATUS_INACTIVE
      ],
      WebhookEvent::CUSTOMERS_DELETED => [
        'group' => 'customers',
        'action' => 'deleted',
        'services' => array('@foo'),
        'id' => NULL,
        'status' => Webhook::STATUS_UNREGISTERED
      ]
    ];

    $shop = new Shop(
      array(
        "id" => 'default',
        "label" => "Default"
      ),
      'lightspeed_ecom_shop'
    );

    // Setup
    $registry = new WebhookRegistry(
      $this->eventDispatcher,
      $this->lightspeedEcomClientFactory,
      $this->logger,
      $this->token,
      $this->cache,
      $this->url_generator
    );

    $this->eventDispatcher->expects($this->once())
      ->method('getListeners')
      ->will($this->returnValue($listeners));

    $this->lightspeedEcomClient->webhooks = $this->getMockBuilder(get_class($this->lightspeedEcomClient->webhooks))
      ->setConstructorArgs(array($this->lightspeedEcomClient))
      ->getMock();
    $this->lightspeedEcomClient->webhooks->expects($this->once())
      ->method('get')
      ->will($this->returnValue($registered_webhooks, TRUE));

    // Consumer method
    $webhooks = $registry->getWebhooks($shop);

    // Assertions on results
    $expected_webhooks_count = count($expected_webhooks);
    $this->assertCount($expected_webhooks_count, $webhooks, "The returned list of webhooks should contains $expected_webhooks_count webhooks.");
    foreach ($webhooks as $webhook) {
      $event_name = "lightspeed_ecom.{$webhook->getGroup()}.{$webhook->getAction()}";
      $this->assertNotEmpty($expected_webhooks[$event_name]);
      $this->assertEquals($expected_webhooks[$event_name]['group'], $webhook->getGroup(), "The group for the $event_name webhook must be {$expected_webhooks[$event_name]['group']}.");
      $this->assertEquals($expected_webhooks[$event_name]['action'], $webhook->getAction(), "The action for the $event_name webhook must be {$expected_webhooks[$event_name]['action']}.");
      $this->assertEquals($expected_webhooks[$event_name]['services'], $webhook->getListeners(), "The services for the $event_name webhook must be [" . implode(", ", $expected_webhooks[$event_name]['services']) . "].", $delta = 0.0, $maxDepth = 10, $canonicalize = true);
      $this->assertEquals($expected_webhooks[$event_name]['id'], $webhook->getId(), "The id for the $event_name webhook must be {$expected_webhooks[$event_name]['id']}.");
      $this->assertEquals($expected_webhooks[$event_name]['status'], $webhook->getStatus(), "The status for the $event_name webhook must be {$expected_webhooks[$event_name]['status']}.");
    }
  }

  public function testDispatch() {

    $registry = new WebhookRegistry(
      $this->eventDispatcher,
      $this->lightspeedEcomClientFactory,
      $this->logger,
      $this->token,
      $this->cache,
      $this->url_generator
    );

    $event = new WebhookEvent(
      'customers',
      'updated',
      'default',
      'en',
      '42',
      array()
    );

    // The expected event name.
    $expected_event_name = implode('.', [WebhookEvent::EVENT_NAMESPACE, 'customers', 'updated']);

    $this->eventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->stringContains($expected_event_name),
        $this->identicalTo($event)
      )
      ->will($this->returnArgument(1));

    $result = $registry->dispatch($event);
    $this->assertEquals($event, $result);
  }

  public function testSynchronize() {

    $shop = $this->getMockBuilder(Shop::class)
      ->setConstructorArgs([
        array(
          "id" => 'default',
          "label" => "Default"
        ),
        'lightspeed_ecom_shop'
      ])
      ->setMethods(['language'])
      ->getMock();
    $shop->method('language')
      ->willReturn(new Language(['id' => 'en']));

    $listeners = [
      WebhookEvent::CUSTOMERS_UPDATED => [
        [(object) ['_serviceId' => 'foo'], 'bar'],
        [(object) ['_serviceId' => 'bar'], 'foo']
      ],
      WebhookEvent::CUSTOMERS_CREATED => [
        [(object) ['_serviceId' => 'bar'], 'foo']
      ],
      WebhookEvent::ORDERS_DELETED => [
        [(object) ['_serviceId' => 'foo'], 'foo']
      ]
    ];

    $registered_webhooks = [
      // The webhook for the lightspeed_ecom.customers.updated event
      // Does not need to be updated.
      [
        "id" => 36,
        "createdAt" => "2013-09-17T23:34:46+02:00",
        "updatedAt" => "2013-09-17T23:35:29+02:00",
        "isActive" => TRUE,
        "itemGroup" => "customers",
        "itemAction" => "updated",
        "language" => [
          "id" => 1,
          "code" => "en",
          "locale" => "en_GB",
          "title" => "English"
        ],
        "format" => "json",
        "address" => "http://example.com/lightspeed_ecom/webhook/default?token=" . $this->token->get($shop)
      ],
      // The webhook for the lightspeed_ecom.customers.created event
      // Has to be updated because of the address is wrong.
      [
        "id" => 37,
        "createdAt" => "2013-09-17T23:34:46+02:00",
        "updatedAt" => "2013-09-17T23:35:29+02:00",
        "isActive" => TRUE,
        "itemGroup" => "customers",
        "itemAction" => "created",
        "language" => [
          "id" => 1,
          "code" => "nl",
          "locale" => "nl_NL",
          "title" => "Nederlands"
        ],
        "format" => "json",
        "address" => "http://example.com/lightspeed_ecom/webhook/default?token=azertyuiop"
      ],
      // Another webhook for the lightspeed_ecom.customers.created event
      // Should not be changed since it not ours
      [
        "id" => 38,
        "createdAt" => "2013-09-17T23:34:46+02:00",
        "updatedAt" => "2013-09-17T23:35:29+02:00",
        "isActive" => TRUE,
        "itemGroup" => "customers",
        "itemAction" => "created",
        "language" => [
          "id" => 1,
          "code" => "nl",
          "locale" => "nl_NL",
          "title" => "Nederlands"
        ],
        "format" => "json",
        "address" => "http://requestb.in/17hcl441"
      ],
      // Webhook for the lightspeed_ecom.customers.deleted event
      // Should be deleted since its ours and not needed
      [
        "id" => 39,
        "createdAt" => "2013-09-17T23:34:46+02:00",
        "updatedAt" => "2013-09-17T23:35:29+02:00",
        "isActive" => TRUE,
        "itemGroup" => "customers",
        "itemAction" => "deleted",
        "language" => [
          "id" => 1,
          "code" => "nl",
          "locale" => "nl_NL",
          "title" => "Nederlands"
        ],
        "format" => "json",
        "address" => "http://example.com/lightspeed_ecom/"
      ]
    ];

    $this->eventDispatcher->expects($this->once())
      ->method('getListeners')
      ->will($this->returnValue($listeners));

    $this->lightspeedEcomClient->webhooks = $this->getMockBuilder(get_class($this->lightspeedEcomClient->webhooks))
      ->setConstructorArgs(array($this->lightspeedEcomClient))
      ->getMock();
    $this->lightspeedEcomClient->webhooks->expects($this->once())
      ->method('get')
      ->will($this->returnValue($registered_webhooks, TRUE));

    $this->lightspeedEcomClient->webhooks->expects($this->once())
      ->method('create')
      ->with($this->callback(function ($fields) {
        return $fields['isActive']
          && ($fields['itemGroup'] == 'orders')
          && ($fields['itemAction'] == 'deleted')
          && ($fields['language'] == 'en')
          && ($fields['format'] == 'json')
          && ($fields['address'] == 'http://example.com/lightspeed_ecom/webhook/default?token=qwertyuiop');
      }));

    $this->lightspeedEcomClient->webhooks->expects($this->once())
      ->method('update')
      ->with(
        $this->identicalTo(37),
        $this->callback(function($fields) {
          return is_array($fields)
            && ($fields['language'] == 'en')
            && ($fields['address'] == "http://example.com/lightspeed_ecom/webhook/default?token=qwertyuiop");
        })
      );

    $this->lightspeedEcomClient->webhooks->expects($this->once())
      ->method('delete')
      ->with($this->identicalTo(39));

    $registry = new WebhookRegistry(
      $this->eventDispatcher,
      $this->lightspeedEcomClientFactory,
      $this->logger,
      $this->token,
      $this->cache,
      $this->url_generator
    );

    $registry->synchronize($shop);

    $this->assertTrue(TRUE);
  }

}
