<?php

namespace Drupal\Tests\api_ai_webhook\Unit;

use Drupal\api_ai_webhook\Plugin\ChatbotApiEntities\PushHandler\ApiAiPushHandler;
use Drupal\chatbot_api_entities\Entity\EntityCollectionInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;

/**
 * Tests api ai push handler.
 *
 * @group api_ai_webhook
 */
class ApiAiPushHandlerTest extends UnitTestCase {

  /**
   * Tests api handler saves.
   */
  public function testSaveConfiguration() {
    // Create a mock and queue some responses.
    $mock = new MockHandler([
      new Response(200, [], Json::encode([
        ['id' => 'bizbang', 'name' => 'terry'],
      ])),
      new Response(200, [], Json::encode(['id' => 'foobar'])),
      new Response(200, [], Json::encode([
        ['id' => 'bizbang', 'name' => 'bob'],
      ])),
      new RequestException("Error getting the list", new Request('GET', 'test')),
      new Response(200, [], Json::encode([
        ['id' => 'bizbang', 'name' => 'bob'],
      ])),
      new RequestException("Error posting", new Request('POST', 'test')),
    ]);

    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler]);

    $logger = new NullLogger();

    $handler = new ApiAiPushHandler([
      'settings' => ['remote_name' => 'bob'],
    ], 'foo', [], $client, new Settings([
      'api_ai_webhook_developer_token' => '123',
    ]), $logger);

    $newCollection = $this->prophesize(EntityCollectionInterface::class);
    $oldCollection = $this->prophesize(EntityCollectionInterface::class);
    $newCollection->isNew()->willReturn(TRUE);
    $newCollection->label()->willReturn('bax');
    $oldCollection->isNew()->willReturn(FALSE);

    // Should POST and request ID.
    $configuration = $handler->saveConfiguration($newCollection->reveal(), []);
    $this->assertEquals('foobar', $configuration['settings']['remote_id']);

    // Should do nothing.
    $configuration = $handler->saveConfiguration($oldCollection->reveal(), []);
    $this->assertEquals([], $configuration);

    // Should get existing ID.
    $configuration = $handler->saveConfiguration($newCollection->reveal(), []);
    $this->assertEquals('bizbang', $configuration['settings']['remote_id']);

    // Should cause an error on GET.
    $this->setExpectedException(EntityStorageException::class);
    $handler->saveConfiguration($newCollection->reveal(), []);

    // Should cause an error on POST.
    $this->setExpectedException(EntityStorageException::class);
    $handler->saveConfiguration($newCollection->reveal(), []);
  }

  /**
   * Test that an existing ID is respected.
   */
  public function testShouldNotFetchIfIdExists() {
    $client = $this->prophesize(ClientInterface::class);
    $client->request()->shouldNotBeCalled();
    $configuration = [
      'settings' => [
        'remote_name' => 'bob',
        'remote_id' => '123',
      ],
    ];
    $handler = new ApiAiPushHandler($configuration, 'foo', [], $client->reveal(), new Settings([
      'api_ai_webhook_developer_token' => '123',
    ]), new NullLogger());
    $newCollection = $this->prophesize(EntityCollectionInterface::class);
    $newCollection->isNew()->willReturn(TRUE);
    $newCollection->label()->willReturn('bax');
    $handler->saveConfiguration($newCollection->reveal(), $configuration);
  }

}
