<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

use Acquia\ContentHubClient\ContentHubClient;
use Acquia\ContentHubClient\Settings;
use Acquia\Hmac\Key;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\acquia_contenthub\Event\HandleWebhookEvent;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ImportTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class ImportTrackingTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'file',
    'node',
    'field',
    'depcalc',
    'acquia_contenthub',
    'acquia_contenthub_subscriber',
  ];

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Contenthub client factory.
   *
   * @var \Drupal\acquia_contenthub\Client\ClientFactory
   */
  protected $clientFactory;

  /**
   * Import queue instance.
   *
   * @var \Drupal\acquia_contenthub_subscriber\ContentHubImportQueue
   */
  protected $importQueue;

  /**
   * Client UUID.
   *
   * @var string
   */
  protected $settingsClientUuid = '00000000-0000-460b-ac74-b6bed08b4441';

  /**
   * Initiator UUID.
   *
   * @var string
   */
  protected $initiatorID = '00000000-abba-47f1-6a54-1e485293a301';

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('acquia_contenthub_subscriber', ['acquia_contenthub_subscriber_import_tracking']);

    $this->importQueue = $this->container->get('acquia_contenthub_subscriber.acquia_contenthub_import_queue');
    $this->dispatcher = $this->container->get('event_dispatcher');

    $content_hub_settings = $this
      ->getMockBuilder(Settings::class)
      ->disableOriginalConstructor()
      ->getMock();
    $content_hub_settings
      ->method('getUuid')
      ->willReturn($this->settingsClientUuid);

    $content_hub_client = $this
      ->getMockBuilder(ContentHubClient::class)
      ->disableOriginalConstructor()
      ->getMock();
    $content_hub_client
      ->method('getSettings')
      ->willReturn($content_hub_settings);

    $client_factory = $this
      ->getMockBuilder(ClientFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $client_factory->method('getClient')->willReturn($content_hub_client);
    $this->clientFactory = $client_factory;
  }

  /**
   * Tests adding items to queue.
   */
  public function testImportQueueOperations() {
    $this->assertEquals(0, $this->importQueue->getQueueCount(), 'By default import queue should be empty.');

    // Simulate adding of 2 content entities.
    $payload = [
      'status' => 'successful',
      'crud' => 'update',
      'assets' => [
        [
          'uuid' => '00000000-0001-460b-ac74-b6bed08b4441',
          'type' => 'drupal8_content_entity',
        ],
        [
          'uuid' => '00000000-0002-4be4-8bf0-c86d895721e9',
          'type' => 'drupal8_config_entity',
        ],
      ],
      'initiator' => $this->initiatorID,
    ];
    $this->invokeImportUpdateAssets($payload);
    $this->assertEquals(1, $this->importQueue->getQueueCount());

    // Add one more item.
    $payload = [
      'status' => 'successful',
      'crud' => 'update',
      'assets' => [
        [
          'uuid' => '00000000-0003-460b-ac74-b6bed08b4441',
          'type' => 'drupal8_content_entity',
        ],
      ],
      'initiator' => $this->initiatorID,
    ];
    $this->invokeImportUpdateAssets($payload);
    $this->assertEquals(2, $this->importQueue->getQueueCount());
  }

  /**
   * Testes wrong assets type.
   *
   * Only items with following types will be tracked:
   * - drupal8_content_entity
   * - drupal8_config_entity.
   */
  public function testTrackingWithWrongAssetType() {
    $payload = [
      'status' => 'successful',
      'crud' => 'update',
      'assets' => [
        [
          'uuid' => '00000000-0003-460b-ac74-b6bed08b4441',
          'type' => 'unknown_entity_type',
        ],
      ],
      'initiator' => $this->initiatorID,
    ];
    $this->invokeImportUpdateAssets($payload);
    $this->assertEquals(0, $this->importQueue->getQueueCount());
  }

  /**
   * Tests tracking with empty assets.
   */
  public function testTrackingWithEmptyAssets() {
    $payload = [
      'status' => 'successful',
      'crud' => 'update',
      'assets' => [],
      'initiator' => $this->initiatorID,
    ];
    $this->invokeImportUpdateAssets($payload);
    $this->assertEquals(0, $this->importQueue->getQueueCount());
  }

  /**
   * Tests identical UUIDs in initiator and asset.
   *
   * We shouldn't import content in case when site is publisher and subscriber
   * at same time.
   */
  public function testTrackingWhenInitiatorAndAssetUuidIsIdentical() {
    $payload = [
      'status' => 'successful',
      'crud' => 'update',
      'assets' => [
        [
          'uuid' => '00000000-0003-460b-ac74-b6bed08b4441',
          'type' => 'drupal8_content_entity',
        ],
      ],
      'initiator' => $this->settingsClientUuid,
    ];
    $this->invokeImportUpdateAssets($payload);
    $this->assertEquals(0, $this->importQueue->getQueueCount());
  }

  /**
   * Tests tracking with wrong payload status.
   */
  public function testTrackingWithWrongPayloadStatus() {
    $payload = [
      'status' => 'any_status_except_successful',
      'crud' => 'update',
      'assets' => [],
      'initiator' => $this->initiatorID,
    ];

    $this->invokeImportUpdateAssets($payload);
    $this->assertEquals(0, $this->importQueue->getQueueCount());
  }

  /**
   * Triggers HANDLE_WEBHOOK event.
   *
   * @param array $payload
   *   Payload data.
   */
  protected function invokeImportUpdateAssets(array $payload) {
    $request = Request::createFromGlobals();

    $key = new Key('id', 'secret');
    $event = new HandleWebhookEvent($request, $payload, $key, $this->clientFactory->getClient());
    $this->dispatcher->dispatch(AcquiaContentHubEvents::HANDLE_WEBHOOK, $event);
  }

}
