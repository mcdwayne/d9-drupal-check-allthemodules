<?php

namespace Drupal\Tests\inmail\Kernel;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\inmail\Tests\DelivererTestTrait;
use Drupal\inmail_test\Plugin\inmail\Deliverer\TestDeliverer;
use Drupal\inmail_test\Plugin\inmail\Handler\ResultKeeperHandler;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behaviour of the MessageProcessor class.
 *
 * @group inmail
 * @requires module past_db
 */
class ProcessorTest extends KernelTestBase {

  use DelivererTestTrait, InmailTestHelperTrait;

  public static $modules = array('inmail', 'inmail_test', 'dblog', 'user', 'system');

  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('dblog', ['watchdog']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests that the processor handles invalid messages by logging.
   */
  public function testMalformedMessage() {
    // Process a malformed message.
    /** @var \Drupal\inmail\MessageProcessorInterface $processor */
    $processor = \Drupal::service('inmail.processor');
    $raw = $this->getMessageFileContents('malformed/unseparated_body.eml');
    // Reset the state to be sure that function is called in the test.
    $deliverer = $this->createTestDeliverer();
    $processor->process('unique_key', $raw, $deliverer);
    // Since the message is invalid, success in processing is never called
    // and state never got changed.
    $this->assertSuccess($deliverer, '');

    // Check last DbLog message.
    $dblog_statement = \Drupal::database()->select('watchdog', 'w')
      ->orderBy('timestamp', 'DESC')
      ->fields('w', ['message'])
      ->execute();
    $dblog_entry = $dblog_statement->fetchAssoc();
    $this->assertEqual('Unable to process message, parser failed with error: %error', $dblog_entry['message']);
  }

  /**
   * Tests the sample message processing.
   */
  public function testMessageProcessing() {
    $raw = <<<EOF
Subject: Hello!
From: Demo User <demo@example.com>
Received: 23 Aug 2016 17:48:6 +0600
Date: 23 Aug 2016 17:48:6 +0600
To: receiver@example.com

Hello world!
EOF;

    /** @var \Drupal\inmail\MessageProcessorInterface $processor */
    $processor = \Drupal::service('inmail.processor');

    AnalyzerConfig::create(['id' => 'test_analyzer', 'plugin' => 'test_analyzer'])->save();
    // Add an unavailable analyzer.
    $unavailable_analyzer = AnalyzerConfig::create([
      'id' => 'unavailable_analyzer',
      'label' => 'Unavailable Analyzer',
      'plugin' => 'unavailable_analyzer',
    ]);
    $unavailable_analyzer->save();

    HandlerConfig::create(['id' => 'result_keeper', 'plugin' => 'result_keeper'])->save();
    $deliverer = $this->createTestDeliverer();
    $processor->process('unique_key', $raw, $deliverer);
    // Assert that success function is called.
    $this->assertSuccess($deliverer, 'unique_key');

    $processor_result = ResultKeeperHandler::getResult();
    /** @var \Drupal\inmail\DefaultAnalyzerResult $default_result */
    $default_result = $processor_result->getAnalyzerResult();

    // Assert "Test Analyzer" updated the account on default result.
    $this->assertEquals('Demo User', $default_result->getAccount()->getDisplayName());
    // Assert the account was switched on handler's level.
    $this->assertEquals('Demo User', ResultKeeperHandler::getAccountName());

    // Assert the requirements messages.
    $plugin = $unavailable_analyzer->getPluginInstance();
    $this->assertEquals([
      'title' => t('Unavailable Analyzer'),
      'description' => t('Unavailable Analyzer cannot be used.'),
      'severity' => REQUIREMENT_ERROR,
    ], $plugin::checkPluginRequirements());
    $this->assertEquals([
      'description' => t('Wrong instance configuration.'),
      'severity' => REQUIREMENT_ERROR,
    ], $plugin->checkInstanceRequirements());
    $this->assertEquals(FALSE, $unavailable_analyzer->isAvailable());
    // The body message has not changed. It implies UnavailableAnalyzer
    // did not run.
    $this->assertEquals('Hello world!', $default_result->getBody());
  }

}
