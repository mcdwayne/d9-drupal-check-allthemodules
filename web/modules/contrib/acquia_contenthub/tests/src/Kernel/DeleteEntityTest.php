<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

use Acquia\ContentHubClient\CDF\CDFObject;
use Acquia\ContentHubClient\ContentHubClient;
use Acquia\ContentHubClient\Settings;
use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\acquia_contenthub_publisher\PublisherTracker;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;

/**
 * Class DeleteEntityTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class DeleteEntityTest extends EntityKernelTestBase {

  public static $modules = ['user', 'system', 'field', 'text', 'filter', 'depcalc', 'acquia_contenthub', 'acquia_contenthub_publisher'];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $settings;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $client;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $factory;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $tracker;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', 'users_data');
    $this->installSchema('acquia_contenthub_publisher', 'acquia_contenthub_publisher_export_tracking');
    $this->user = $this->createUser();
    $uuid = $this->user->uuid();

    $origin_uuid = '00000000-0000-0001-0000-123456789123';

    $cdfObject = new CDFObject('drupal8_content_entity', $uuid, 'foo', 'bar', $origin_uuid);

    $this->settings = $this->prophesize(Settings::class);
    $this->settings->getUuid()->willReturn($origin_uuid);
    $this->settings->getWebhook('uuid')->willReturn($origin_uuid);

    $deleteEntityResponse = $this->prophesize(ResponseInterface::class);
    $deleteEntityResponse->getStatusCode()->willReturn(202);

    $deleteInterestResponse = $this->prophesize(ResponseInterface::class);
    $deleteInterestResponse->getStatusCode()->willReturn(200);

    $this->client = $this->prophesize(ContentHubClient::class);
    $this->client->getEntity($uuid)->willReturn($cdfObject);
    $this->client->getSettings()->willReturn($this->settings->reveal());
    $this->client->deleteEntity($uuid)->willReturn($deleteEntityResponse->reveal());
    $this->client->deleteInterest($this->user->uuid(), $origin_uuid)->willReturn($deleteInterestResponse->reveal());

    $this->factory = $this->prophesize(ClientFactory::class);
    $this->factory->getClient()->willReturn($this->client->reveal());
    $this->container->set('acquia_contenthub.client.factory', $this->factory->reveal());

    $this->tracker = $this->prophesize(PublisherTracker::class);
    $this->tracker->get(Argument::any())->will(
      function ($uuid) {
        $query = \Drupal::database()->select('acquia_contenthub_publisher_export_tracking', 't')
          ->fields('t', ['entity_uuid']);
        $query->condition('entity_uuid', $uuid);
        return $query->execute()->fetchObject();
      }
    );
    $this->tracker->delete(Argument::any())->will(
      function($uuid) {
        $query = \Drupal::database()->delete('acquia_contenthub_publisher_export_tracking');
        $query->condition('entity_uuid', $uuid);
        return $query->execute();
      }
    );
    $this->container->set('acquia_contenthub_publisher.tracker', $this->tracker->reveal());
  }

  /**
   * Tests the expected flow of a full entity delete process.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEntityDelete() {
    $this->user->delete();
    $this->tracker->get($this->user->uuid())->shouldBeCalled();
    $this->tracker->delete($this->user->uuid())->shouldBeCalled();
    $this->client->getEntity($this->user->uuid())->shouldBeCalled();
    $this->client->deleteEntity($this->user->uuid())->shouldBeCalled();
    $this->client->deleteInterest($this->user->uuid(), $this->container->get('acquia_contenthub.client.factory')->getClient()->getSettings()->getWebhook('uuid'))->shouldBeCalled();
  }

}
