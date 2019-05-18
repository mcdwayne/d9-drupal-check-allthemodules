<?php

namespace Drupal\Tests\chatbot_api\Kernel;

use Drupal\chatbot_api\IntentRequestInterface;
use Drupal\chatbot_api\IntentResponseInterface;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\Argument;

/**
 * Tests Chatbot API Intent Plugin system basic functionality.
 *
 * @group chatbot_api
 */
class ChatbotApiIntentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'chatbot_api',
    'chatbot_api_helloworld_intent_test',
  ];

  /**
   * Test intent is loaded and processed.
   */
  public function testIntentLoadingAndProcessing() {

    $request = $this->prophesize(IntentRequestInterface::class);
    $response = $this->prophesize(IntentResponseInterface::class);

    $output = '';
    $response->setIntentResponse(Argument::type('string'))->will(function ($args) use (&$output) {
      $output = $args[0];
    });

    /** @var \Drupal\chatbot_api\Plugin\IntentPluginManager $manager */
    $manager = $this->container->get('plugin.manager.chatbot_intent_plugin');
    $configuration = [
      'request' => $request->reveal(),
      'response' => $response->reveal(),
    ];
    $plugin = $manager->createInstance('HelloWorldTest', $configuration);
    $plugin->process();

    // Assert HelloWorldTest setIntentResponse() method has been called.
    $this->assertSame($output, 'Hello World Test');

  }

}
