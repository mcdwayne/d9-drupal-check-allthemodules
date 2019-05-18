<?php

namespace Drupal\Tests\inmail\Kernel;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\inmail\Tests\DelivererTestTrait;
use Drupal\inmail_test\Plugin\inmail\Deliverer\TestDeliverer;
use Drupal\inmail_test\Plugin\inmail\Handler\ResultKeeperHandler;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests analyzers.
 *
 * @group inmail
 */
class AnalyzerTest extends KernelTestBase {

  use DelivererTestTrait, InmailTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('inmail', 'inmail_test', 'user', 'system');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installConfig(['inmail']);
    \Drupal::configFactory()->getEditable('inmail.settings')
      ->set('return_path', 'bounces@example.com')
      ->save();
  }

  /**
   * Tests an entire processor pass from the aspect of order of analyzers.
   */
  public function testEffectivePriority() {
    // This message is designed to challenge the priority in which analyzers are
    // invoked: if priority is not working correctly, StandardDSNAnalyzer comes
    // before VerpAnalyzer (because of alphabetical sorting?) and sets the
    // recipient property from the Final-Recipient part of the DSN report.
    // With correct priorities, VerpAnalyzer will come first and set the
    // property using the more reliable VERP address.
    $raw = $this->getMessageFileContents('/bounce/invalid-username.eml');

    /** @var \Drupal\inmail\MessageProcessorInterface $processor */
    $processor = \Drupal::service('inmail.processor');

    AnalyzerConfig::create(['id' => 'test_analyzer', 'plugin' => 'test_analyzer'])->save();
    HandlerConfig::create(array('id' => 'result_keeper', 'plugin' => 'result_keeper'))->save();
    // Reset the state to be sure that function is called in the test.
    $deliverer = $this->createTestDeliverer();
    $processor->process('unique_key', $raw, $deliverer);
    // Assert that success function is called.
    $this->assertSuccess($deliverer, 'unique_key');

    $processor_result = ResultKeeperHandler::getResult();
    /** @var \Drupal\inmail\DefaultAnalyzerResult $result */
    $result = $processor_result->getAnalyzerResult();
    /** @var \Drupal\inmail\DefaultAnalyzerResult $default_result */
    $default_result = $processor_result->getAnalyzerResult();
    $bounce_context = $result->getContext('bounce');
    /** @var \Drupal\inmail\Plugin\DataType\BounceData $bounce_data */
    $bounce_data = $bounce_context->getContextData();

    $this->assertEqual($bounce_data->getRecipient(), 'verp-parsed@example.org');

    $this->assertEquals('Demo User', $default_result->getAccount()->getDisplayName());
    $this->assertEquals('Sample context value', $default_result->getContext('test')->getContextValue());

    // Adding already defined context should overwrite the existing one.
    $default_result->setContext('test', new Context(new ContextDefinition('string'), 'New value'));
    $this->assertEquals('New value', $default_result->getContext('test')->getContextValue());

    // Accessing undefined context should throw exception.
    $exception_message = 'Context "invalid_context_name" does not exist.';
    try {
      $default_result->getContext('invalid_context_name');
      $this->fail($exception_message);
    }
    catch (\InvalidArgumentException $e) {
      $this->assertEquals($exception_message, $e->getMessage());
    }

    // Try to ensure context that is being already set with invalid data type.
    $exception_message = 'Invalid data type invalid_data_type has been given.';
    try {
      $result->ensureContext('bounce', 'invalid_data_type');
      $this->fail($exception_message);
    }
    catch (\InvalidArgumentException $e) {
      $this->assertEquals($exception_message, $e->getMessage());
    }

    // Try to ensure context on non-existing context with invalid data type.
    $exception_message = 'The "invalid_data_type" plugin does not exist.';
    try {
      $result->ensureContext('non_existing_context', 'invalid_data_type');
      $this->fail($exception_message);
    }
    catch (\Exception $e) {
      $this->assertEquals($exception_message, $e->getMessage());
    }
  }

}
