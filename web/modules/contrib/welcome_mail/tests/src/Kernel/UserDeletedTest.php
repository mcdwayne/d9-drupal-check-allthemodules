<?php

namespace Drupal\Tests\welcome_mail\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Class UserDeletedTest.
 *
 * @group welcome_mail
 */
class UserDeletedTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['welcome_mail', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->user = $this->createUser([]);
  }

  /**
   * Test that the fact that we delete a user does not crash the entire queue.
   */
  public function testHandleDeleted() {
    $queue = $this->container->get('queue')->get(WELCOME_MAIL_QUEUE_NAME);
    $queue->createItem($this->user->id());
    $this->assertEquals(1, $queue->numberOfItems());
    /** @var \Drupal\Core\Queue\QueueWorkerManager $queue_runner */
    $queue_manager = $this->container->get('plugin.manager.queue_worker');
    $worker = $queue_manager->createInstance(WELCOME_MAIL_QUEUE_NAME);
    $item = $queue->claimItem();
    $this->user->delete();
    // Now, running this should not throw an exception.
    $worker->processItem($item->data);
  }

}
