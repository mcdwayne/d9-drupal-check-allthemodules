<?php

namespace Drupal\Tests\message_thread\Functional;

use Drupal\message_thread\Entity\MessageThread;
use Drupal\user\Entity\User;

/**
 * Tests message thread creation and default values.
 *
 * @group message_thread
 */
class MessageThreadCreateTest extends MessageThreadTestBase {

  /**
   * The user object.
   *
   * @var \Drupal\user\Entity\User
   */
  private $user;

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

    $this->user = $this->drupalcreateuser();
  }

  /**
   * Tests if message create sets the default uid to currently logged in user.
   */
  public function testMessageThreadCreateDefaultValues() {
    // Login our user to create message.
    $this->drupalLogin($this->user);

    $template = 'dummy_message_thread';
    // Create message to be rendered without setting owner.
    $message_thread_template = $this->createMessageThreadTemplate($template, 'Dummy message thread', '', ['[message_thread:author:name]']);
    $message_thread = MessageThread::create(['template' => $message_thread_template->id()]);

    $message_thread->save();

    /* @var Message $message_thread */
    $this->assertEqual($this->user->id(), $message_thread->getOwnerId(), 'The default value for uid was set correctly.');
  }

}
