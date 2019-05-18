<?php

namespace Drupal\Tests\message_thread\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\message_thread\Entity\MessageThread;
use Drupal\simpletest\UserCreationTrait;

/**
 * Kernel tests for the Message entity.
 *
 * @group message_thread
 *
 * @coversDefaultClass \Drupal\message_thread\Entity\MessageThread
 */
class MessageThreadTest extends KernelTestBase {

  use MessageThreadTemplateCreateTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['filter', 'message_thread', 'user', 'system'];

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A message template to test with.
   *
   * @var \Drupal\message\MessageTemplateInterface
   */
  protected $messageThreadTemplate;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['filter']);
    $this->installEntitySchema('message_thread');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->messageThreadTemplate = $this->createMessageThreadTemplate(Unicode::strtolower($this->randomMachineName()), $this->randomString(), $this->randomString(), []);
  }

  /**
   * Tests attempting to create a message without a template.
   *
   * @expectedException \Drupal\message\MessageException
   */
  public function testMissingTemplate() {
    $message_thread = MessageThread::create(['template' => 'missing']);
    $message_thread->save();
  }

  /**
   * Tests getting the user.
   */
  public function testGetOwner() {
    $message_thread = MessageThread::create(['template' => $this->messageThreadTemplate->id()]);
    $account = $this->createUser();
    $message_thread->setOwner($account);
    $this->assertEquals($account->id(), $message_thread->getOwnerId());

    $owner = $message_thread->getOwner();
    $this->assertEquals($account->id(), $owner->id());
  }

}
