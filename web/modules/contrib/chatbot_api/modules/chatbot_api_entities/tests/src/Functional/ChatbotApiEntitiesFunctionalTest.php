<?php

namespace Drupal\Tests\chatbot_api_entities\Functional;

use Drupal\chatbot_api_entities\Entity\EntityCollection;
use Drupal\Core\Url;
use Drupal\simpletest\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\chatbot_api_entities\Traits\ChatbotApiEntitiesTestTrait;

/**
 * Tests Chatbot API Entities UI functionality.
 *
 * @group chatbot_api
 */
class ChatbotApiEntitiesFunctionalTest extends BrowserTestBase {

  use ChatbotApiEntitiesTestTrait;
  use BlockCreationTrait;

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
    'block',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupEntityTestBundle();
    $this->placeBlock('local_actions_block');
  }

  /**
   * Tests admin UI.
   */
  public function testAdminUi() {
    $assert = $this->assertSession();
    $collectionUrl = Url::fromRoute('entity.chatbot_api_entities_collection.collection');
    $this->drupalGet($collectionUrl);
    $assert->statusCodeEquals(403);
    $admin = $this->createUser(['administer chatbot api entities']);
    $this->drupalLogin($admin);
    $this->drupalGet($collectionUrl);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Entity collection');
    $this->clickLink('Add collection');
    $assert->statusCodeEquals(200);
    $label = 'Send entity test some bundle';
    $id = 'entity_test_some_bundle';
    $this->submitForm([
      'id' => $id,
      'label' => $label,
      'entity_type' => 'entity_test:some_bundle',
    ], 'Change Entity type');
    // Now should be a button.
    $this->submitForm([
      'synonyms' => 'field_synonyms',
      'enabled_query_handlers[default:entity_test]' => 1,
      'enabled_push_handlers[chatbot_api_entities_test]' => 1,
      // Intentionally blank.
      'push_handler_configuration[chatbot_api_entities_test][settings][remote_id]' => '',
    ], 'Save');
    $assert->pageTextContains('Remote ID is required');
    $this->submitForm([
      'push_handler_configuration[chatbot_api_entities_test][settings][remote_id]' => 'Foobar',
    ], 'Save');
    $assert->responseContains(t('Created the %label collection.', [
      '%label' => $label,
    ]));
    $this->cronRun();
    $config = EntityCollection::load($id);
    $this->assertNotEmpty($config);
    $asArray = $config->toArray();
    $this->assertEquals([
      'chatbot_api_entities_test' => [
        'settings' => [
          'remote_id' => 'Foobar',
          'added_at_save_time' => 'entity_test_some_bundle',
        ],
        'id' => 'chatbot_api_entities_test',
      ],
    ], $asArray['push_handlers']);
    $this->assertEquals([
      'default:entity_test' => [
        'id' => 'default:entity_test',
      ],
    ], $asArray['query_handlers']);
    $this->drupalGet($config->toUrl('edit-form'));
    // Make sure defaults are shown.
    $assert->fieldValueEquals('push_handler_configuration[chatbot_api_entities_test][settings][remote_id]', 'Foobar');
  }

}
