<?php

namespace Drupal\inmail_demo\Tests;

use Drupal\inmail\Entity\HandlerConfig;
use Drupal\inmail_test\Plugin\inmail\Handler\ResultKeeperHandler;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the demo module for Inmail.
 *
 * @group inmail
 */
class InmailDemoTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'inmail_demo',
    'inmail_test',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $deliverer_config = HandlerConfig::create([
      'id' => 'result_keeper',
      'plugin' => 'result_keeper',
    ]);
    $deliverer_config->save();

    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the paste form.
   */
  protected function testPasteForm() {
    $this->drupalGet('admin/config/system/inmail/paste');
    $this->assertResponse(403);
    $this->drupalLogin($this->drupalCreateUser(['administer inmail']));
    $this->drupalGet('admin/config/system/inmail');
    $this->assertText('Paste email');

    // Test empty submission.
    $this->drupalGet('admin/config/system/inmail/paste');
    $this->drupalPostForm(NULL, [], t('Process email'));
    $this->assertText('Error while processing message.');
    $this->assertText('Unable to process message, parser failed with error: Failed to split header from body');

    // Process default example.
    $this->assertFieldByName('deliverer', 'paste');
    $this->drupalPostAjaxForm(NULL, [], ['op' => t('Load example')]);
    $this->drupalPostForm(NULL, [], t('Process email'));
    $this->assertText('The message has been processed.');
    // Check processed result.
    $this->assertEqual('Re: Hello', ResultKeeperHandler::getMessage()->getSubject());
    $this->assertEqual('paste', ResultKeeperHandler::getResult()->getDeliverer()->id());
  }

}
