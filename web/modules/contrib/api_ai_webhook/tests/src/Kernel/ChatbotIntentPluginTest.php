<?php

namespace Drupal\Tests\api_ai_webhook\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\UserCreationTrait;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class for testing web intents.
 *
 * @group api_ai_webhook
 */
class ChatbotIntentPluginTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'chatbot_api',
    'api_ai_webhook',
    'user',
    'api_ai_webhook_test',
    'filter',
    'text',
    'system',
    'field',
    'chatbot_api_apiai',
    'views',
  ];

  /**
   * Kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['filter', 'user']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    // Create anonymous user.
    $anonymous = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'uid' => 0,
        'status' => 0,
        'name' => '',
      ]);
    $anonymous->save();
    /** @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = $this->container->get('entity_type.manager')
      ->getStorage('user_role')
      ->load(RoleInterface::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access content');
    $anonymous_role->save();
    $fields[] = ['user', 'user', 'field_user_info'];
    foreach ($fields as $detail) {
      list($entity_type, $bundle, $field_name) = $detail;
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => 'text_long',
        'cardinality' => 1,
      ])->save();

      $field_config = FieldConfig::create([
        'field_name' => $field_name,
        'label' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'required' => FALSE,
      ]);
      $field_config->save();
    }
    $this->httpKernel = $this->container->get('http_kernel');
    // Setup some sample content.
    $users = ['John', 'Bob', 'Sally', 'Rhonda'];
    foreach ($users as $name) {
      $user = $this->createUser([], $name);
      $user->field_user_info = [
        'value' => sprintf('%s is awesome, everything you need to know about %s.', $name, $name),
        'format' => 'plain_text',
      ];
      $user->save();
    }
    // Prime flood.
    $this->container->get('flood')
      ->register('api_ai_auth.failed_login_ip', 3600, '127.0.0.1');
  }

  /**
   * Data provider for testIntents.
   *
   * @return array
   *   Test cases.
   */
  public function providerIntents() {
    return [
      'User info intent' => [
        'UserInfo',
        ['Staff' => 'John'],
        'John is awesome, everything you need to know about John.',
      ],
    ];
  }

  /**
   * Tests intents work the correct way.
   *
   * @param string $intentName
   *   Intent name.
   * @param array $parameters
   *   Parameters, keyed by name.
   * @param string $expectedResponse
   *   Expected response.
   *
   * @dataProvider providerIntents
   */
  public function testIntents($intentName, array $parameters = [], $expectedResponse = '') {
    $body = sprintf('{
  "responseId": "7811ac58-5bd5-4e44-8d06-6cd8c67f5406",
  "session": "projects/your-agents-project-id/agent/sessions/88d13aa8-2999-4f71-b233-39cbf3a824a0",
  "queryResult": {
    "queryText": "user\'s original query to your agent",
    "parameters": %s,
    "allRequiredParamsPresent": true,
    "fulfillmentText": "Text defined in Dialogflow\'s console for the intent that was matched",
    "fulfillmentMessages": [
      {
        "text": {
          "text": [
            "Text defined in Dialogflow\'s console for the intent that was matched"
          ]
        }
      }
    ],
    "outputContexts": [
      {
        "name": "projects/your-agents-project-id/agent/sessions/88d13aa8-2999-4f71-b233-39cbf3a824a0/contexts/generic-context-name",
        "lifespanCount": 5,
        "parameters": {
          "param1": "foo",
          "param2": "bar"
        }
      }
    ],
    "intent": {
      "name": "projects/your-agents-project-id/agent/intents/29bcd7f8-f717-4261-a8fd-2d3e451b8af8",
      "displayName": "%s",
      "webhookState": 2
    },
    "intentDetectionConfidence": 1,
    "diagnosticInfo": {},
    "languageCode": "en"
  },
  "originalDetectIntentRequest": {}
}', json_encode($parameters), $intentName);
    $request = Request::create('/api.ai/webhook', 'POST', [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], $body);
    $response = json_decode($this->httpKernel->handle($request)
      ->getContent(), TRUE);

    $speech = $response['fulfillmentText'];
    $this->assertContains($expectedResponse, $speech);

    $decoded_body = json_decode($body, TRUE);
    $this->assertEquals($response['outputContexts'][0]['name'], $decoded_body['queryResult']['outputContexts'][0]['name']);

    // Test contexts are passed in/out correctly.
    $this->assertEquals($response['outputContexts'][0]['parameters']['param3'], $decoded_body['queryResult']['outputContexts'][0]['parameters']['param1'] . $decoded_body['queryResult']['outputContexts'][0]['parameters']['param2']);
  }

}
