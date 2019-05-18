<?php

namespace Drupal\Tests\message_thread\Functional;

use Drupal\message_thread\Entity\MessageThread;

/**
 * Test the views text handler.
 *
 * @group message_thread
 */
class MessageThreadTextHandlerTest extends MessageThreadTestBase {

  /**
   * The user object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['filter_test'];

  /**
   * Currently experiencing schema errors.
   *
   * @var strictConfigSchema
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser(['administer message thread templates']);
  }

  /**
   * Testing the deletion of messages in cron according to settings.
   */
  public function testTextHandler() {
    $text = [
      ['value' => 'Dummy message thread', 'format' => 'filtered_html'],
    ];
    $this->createMessageThreadTemplate('dummy_message_thread', 'Dummy message thread', '', $text);
    MessageThread::create(['template' => 'dummy_message_thread'])->save();

    $this->drupalLogin($this->account);
    $this->drupalGet('admin/structure/message-threads');
    $this->assertText('Dummy message thread');
  }

}
