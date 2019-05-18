<?php

namespace Drupal\Tests\chatbot_api\Kernel;

use Drupal\chatbot_api\IntentRequestInterface;
use Drupal\chatbot_api\IntentResponseInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\RoleInterface;
use Drupal\views\Tests\ViewTestData;
use Prophecy\Argument;

/**
 * Tests Chatbot Api Views Intent display plugin.
 *
 * @group chatbot_api
 */
class ChatbotViewsIntentTest extends ViewsKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'chatbot_api',
    'chatbot_api_views_intent_test',
    'user',
    'options',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_chatbot_api_display'];

  /**
   * {@inheritdoc}
   */
  public function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installConfig('user');
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
    $anonymous_role->grantPermission('access user profiles');
    $anonymous_role->save();

    $this->createUser([], 'Alice');
    $this->createUser([], 'Bob');
    $this->createUser([], 'Chris');

    ViewTestData::createTestViews(get_class($this), ['chatbot_api_views_intent_test']);
  }

  /**
   * Tests the intent is loaded and processed.
   */
  public function testIntentIsLoadedAndProcessed() {

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $request = $this->prophesize(IntentRequestInterface::class);
    $response = $this->prophesize(IntentResponseInterface::class);

    // Assign $this to $class in order to be used inside anonymous functions
    // scope.
    $class = $this;
    $response->setIntentResponse(Argument::type('string'))->will(function ($args) use ($class) {
      $class->assertSame($args[0], 'Alice');
    });
    $response->addIntentAttribute(Argument::type('string'), Argument::type('integer'))->will(function ($args) use ($class) {
      $class->assertSame($args[0], 'TopUsersIterator');
      $class->assertSame($args[1], 1);
    });

    /** @var \Drupal\chatbot_api\Plugin\IntentPluginManager $manager */
    $manager = $this->container->get('plugin.manager.chatbot_intent_plugin');
    $configuration = [
      'request' => $request->reveal(),
      'response' => $response->reveal(),
    ];
    $plugin = $manager->createInstance('TopUsers', $configuration);

    // Process the plugin request/response within a fake render context.
    $renderer->executeInRenderContext(new RenderContext(), function () use ($plugin) {
      $plugin->process();
    });

  }

  /**
   * Tests the iterator / pagin mechanism.
   */
  public function testViewsIntentIterator() {

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $request = $this->prophesize(IntentRequestInterface::class);
    $response = $this->prophesize(IntentResponseInterface::class);

    // Fake a second iteration request.
    $request->getIntentAttribute(Argument::exact('TopUsersIterator'), Argument::type('integer'))->willReturn(1);
    $request->getIntentAttribute(Argument::type('string'))->willReturn(FALSE);
    $request->getIntentSlot(Argument::any())->willReturn(FALSE);

    // Assign $this to $class in order to be used inside anonymous functions
    // scope.
    $class = $this;
    $response->setIntentResponse(Argument::type('string'))->will(function ($args) use ($class) {
      $class->assertSame($args[0], 'Bob');
    });
    $response->addIntentAttribute(Argument::type('string'), Argument::type('integer'))->will(function ($args) use ($class) {
      $class->assertSame($args[0], 'TopUsersIterator');
      $class->assertSame($args[1], 2);
    });

    /** @var \Drupal\chatbot_api\Plugin\IntentPluginManager $manager */
    $manager = $this->container->get('plugin.manager.chatbot_intent_plugin');
    $configuration = [
      'request' => $request->reveal(),
      'response' => $response->reveal(),
    ];
    $plugin = $manager->createInstance('TopUsers', $configuration);

    // Process the plugin request/response within a fake render context.
    $renderer->executeInRenderContext(new RenderContext(), function () use ($plugin) {
      $plugin->process();
    });

  }

}
