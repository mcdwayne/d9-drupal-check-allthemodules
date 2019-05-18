<?php

namespace Drupal\Tests\inmail\Kernel;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\inmail\Tests\DelivererTestTrait;
use Drupal\inmail_test\Plugin\inmail\Deliverer\TestDeliverer;
use Drupal\inmail_test\Plugin\inmail\Handler\ResultKeeperHandler;
use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the VERP mechanism.
 *
 * @group inmail
 * @requires module past_db
 */
class VerpTest extends KernelTestBase {

  use DelivererTestTrait, InmailTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('inmail', 'inmail_test', 'system', 'dblog', 'user');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['inmail', 'system']);
    $this->installSchema('dblog', ['watchdog']);
    \Drupal::configFactory()->getEditable('system.site')
      ->set('mail', 'bounces@example.com')
      ->save();
  }

  /**
   * Test the VERP mechanism.
   */
  public function testVerp() {
    // Send a message and check the modified Return-Path.
    $recipient = 'user@example.org';
    $expected_returnpath = 'bounces+user=example.org@example.com';

    $message = \Drupal::service('plugin.manager.mail')->mail('inmail_test', 'VERP', $recipient, LanguageInterface::LANGCODE_DEFAULT);
    $this->assertEqual($message['headers']['Return-Path'], $expected_returnpath);
    $this->assertTrue($message['send']);

    // Enable ResultKeeperHandler.
    HandlerConfig::create(array('id' => 'result_keeper', 'plugin' => 'result_keeper'))->save();
    // Disable the StandardDSNAnalyzer because it also reports the correct
    // recipient address.
    AnalyzerConfig::load('dsn')->disable()->save();

    // Process a bounce message with a VERP-y 'To' header, check the parsing.
    $raw = $this->getMessageFileContents('bounce/mailbox-full.eml');
    /** @var \Drupal\inmail\MessageProcessorInterface $processor */
    $processor = \Drupal::service('inmail.processor');
    // Reset the state to be sure that function success is called in the test.
    $deliverer = $this->createTestDeliverer();
    $processor->process('unique_key', $raw, $deliverer);
    // Assert that success function is called.
    $this->assertSuccess($deliverer, 'unique_key');

    /** @var \Drupal\inmail\DefaultAnalyzerResult $result */
    $result = ResultKeeperHandler::getResult()->getAnalyzerResult();
    if (!$result->hasContext('bounce')) {
      return;
    }
    $bounce_context = $result->getContext('bounce');

    /** @var \Drupal\inmail\Plugin\DataType\BounceData $bounce_data */
    $bounce_data = $bounce_context->getContextData();
    $parsed_recipient = $bounce_data->getRecipient();
    $this->assertEqual($parsed_recipient, $recipient);

    // VERP should be skipped for messages with Cc recipients.
    $message = \Drupal::service('plugin.manager.mail')->mail('inmail_test', 'cc', $recipient, LanguageInterface::LANGCODE_DEFAULT);
    $this->assertEqual($this->getLatestLogMessage()['message'], 'Cannot use VERP for message with Cc/Bcc recipients, message ID: @id');
    $this->assertFalse($message['send']);

    // VERP should be skipped when there are multiple recipients.
    $recipient = 'alice@example.org, bob@example.org';
    $message = \Drupal::service('plugin.manager.mail')->mail('inmail_test', 'VERP', $recipient, LanguageInterface::LANGCODE_DEFAULT);
    $this->assertEqual($this->getLatestLogMessage()['message'], 'Cannot use VERP for multiple recipients, message ID: @id');
    $this->assertFalse($message['send']);
  }

  /**
   * Returns the latest watchdog entry.
   *
   * @return array
   *   The latest log entry, as an associative array.
   */
  protected function getLatestLogMessage() {
    return \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['message'])
      ->orderBy('wid', 'DESC')
      ->execute()
      ->fetchAssoc();
  }

}
