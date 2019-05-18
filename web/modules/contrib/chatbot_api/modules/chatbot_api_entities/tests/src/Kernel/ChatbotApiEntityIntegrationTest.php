<?php

namespace Drupal\Tests\chatbot_api_entities\Kernel;

use Drupal\chatbot_api_entities_test\Plugin\ChatbotApiEntities\PushHandler\ChatbotApiEntitiesTestHandler;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\chatbot_api_entities\Entity\EntityCollection;
use Drupal\Tests\chatbot_api_entities\Traits\ChatbotApiEntitiesTestTrait;

/**
 * Tests chatbot api integration with entity hooks.
 *
 * @group chatbot_api
 */
class ChatbotApiEntityIntegrationTest extends KernelTestBase {

  use ChatbotApiEntitiesTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'chatbot_api',
    'chatbot_api_entities',
    'chatbot_api_entities_test',
    'field',
    'text',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('entity_test');
    $this->setupEntityTestBundle();
  }

  /**
   * Test entity events.
   */
  public function testEntityEventsTriggerUpdatesInPlugin() {
    $config = EntityCollection::create([
      'id' => 'entity_test_some_bundle',
      'label' => 'Send entity test some bundle',
      'entity_type' => 'entity_test',
      'bundle' => 'some_bundle',
      'synonyms' => 'field_synonyms',
      'query_handlers' => [
        'default:entity_test' => [
          'id' => 'default:entity_test',
          'settings' => ['test' => 'setting'],
        ],
      ],
      'push_handlers' => [
        ChatbotApiEntitiesTestHandler::STATE_KEY => [
          'settings' => ['remote_id' => 'Foobar'],
          'id' => ChatbotApiEntitiesTestHandler::STATE_KEY,
        ],
      ],
    ]);
    $config->save();
    $asArray = $config->toArray();
    $this->assertEquals('entity_test_some_bundle', $asArray['push_handlers'][ChatbotApiEntitiesTestHandler::STATE_KEY]['settings']['added_at_save_time']);

    // Create first entity.
    $entity_test = EntityTest::create([
      'type' => 'some_bundle',
      'field_synonyms' => [
        'bob',
        'alice',
        'terry',
      ],
      'name' => 'davo',
    ]);
    $entity_test->save();
    $this->cronRun();

    // Assert push handler was called.
    $sent = $this->container->get('state')
      ->get(ChatbotApiEntitiesTestHandler::STATE_KEY, []);
    $this->assertEquals([
      'Foobar' => [
        ['value' => 'davo', 'synonyms' => ['bob', 'alice', 'terry']],
      ],
    ], $sent);

    // Create another entity.
    $second_entity = EntityTest::create([
      'type' => 'some_bundle',
      'field_synonyms' => [
        'wayne',
        'tez',
        'barry',
      ],
      'name' => 'rod',
    ]);
    $second_entity->save();
    $this->cronRun();

    // Assert both have been sent.
    $sent = $this->container->get('state')
      ->get(ChatbotApiEntitiesTestHandler::STATE_KEY, []);
    $this->assertEquals([
      'Foobar' => [
        ['value' => 'davo', 'synonyms' => ['bob', 'alice', 'terry']],
        ['value' => 'rod', 'synonyms' => ['wayne', 'tez', 'barry']],
      ],
    ], $sent);

    // Now update the first one.
    $entity_test->field_synonyms = [
      'bob',
      'alice',
      'terry',
      'jimmy',
    ];
    $entity_test->save();
    $this->cronRun();

    // Assert updated.
    $sent = $this->container->get('state')
      ->get(ChatbotApiEntitiesTestHandler::STATE_KEY, []);
    $this->assertEquals([
      'Foobar' => [
        ['value' => 'davo', 'synonyms' => ['bob', 'alice', 'terry', 'jimmy']],
        ['value' => 'rod', 'synonyms' => ['wayne', 'tez', 'barry']],
      ],
    ], $sent);

    // Now delete.
    $second_entity->delete();
    $this->cronRun();

    // Assert updated.
    $sent = $this->container->get('state')
      ->get(ChatbotApiEntitiesTestHandler::STATE_KEY, []);
    $this->assertEquals([
      'Foobar' => [
        ['value' => 'davo', 'synonyms' => ['bob', 'alice', 'terry', 'jimmy']],
      ],
    ], $sent);
  }

}
