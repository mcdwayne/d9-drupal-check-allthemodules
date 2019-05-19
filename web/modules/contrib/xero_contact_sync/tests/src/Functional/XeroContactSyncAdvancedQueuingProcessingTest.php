<?php

namespace Drupal\Tests\xero_contact_sync\Functional;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\user\Entity\User;
use Psr\Log\LogLevel;
use Radcliffe\Xero\XeroClient;

/**
 * Tests the module queues execution when there is a content created.
 *
 * @group xero_contact_sync
 * @group legacy
 */
class XeroContactSyncAdvancedQueuingProcessingTest extends XeroContactSyncQueuingProcessingTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user', 'xero_contact_sync', 'advancedqueue', 'views'];

  protected function setUp() {
    parent::setUp();

    $data = [
      'id' => 'xero_contact_sync',
      'label' => 'Xero Contact Sync',
      'backend' => 'database',
      'backend_configuration' => ['lease_time' => 60],
      'processor' => 'cron',
      'processing_time' => 60,
      'locked' => TRUE,
    ];
    $queue = Queue::create($data);
    $queue->save();
  }

  protected function runQueueJob() {
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('xero_contact_sync');

    if ($queue->getBackend()->countJobs() == 0) {
      $queue->getBackend()->enqueueJob(Job::create('xero_contact_sync', ['user_id' => '23']));
    }

    /** @var \Drupal\advancedqueue\ProcessorInterface $queue_processor */
    $queue_processor = \Drupal::service('advancedqueue.processor');
    $queue_processor->processQueue($queue);
  }

  public function testUserIsCreatedIfDidntExist() {
    parent::testUserIsCreatedIfDidntExist();

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/config/system/queues/jobs/xero_contact_sync');
    $this->assertSession()->responseContains('Success');
    $this->assertSession()->responseContains('Remote user matched for Michael Jordan.');
  }

  public function testUserIsReferencedIfFoundByContactNumber() {
    parent::testUserIsReferencedIfFoundByContactNumber();

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/config/system/queues/jobs/xero_contact_sync');
    $this->assertSession()->responseContains('Success');
    $this->assertSession()->responseContains('Remote user matched for Michael Jordan.');
  }

  public function testUserIsReferencedIfFoundByEmail() {
    parent::testUserIsReferencedIfFoundByEmail();

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/config/system/queues/jobs/xero_contact_sync');
    $this->assertSession()->responseContains('Success');
    $this->assertSession()->responseContains('Remote user matched for Michael Jordan.');
  }

  public function testFailureCreating() {
    $data = [
      'uid' => 23,
      'name' => 'Michael Jordan',
      'firstname' => 'Michael',
      'lastname' => 'Jordan',
      'mail' => 'mj23@example.com',
    ];
    $this->logger->expects($this->once())
      ->method('log')
      ->with(LogLevel::ERROR, 'Cannot create user Michael Jordan, operation failed.');

    $response = $this->getMockResponseForEmpty();
    $this->xeroClient->expects($this->at(0))
      ->method('__call')
      ->with('get', ['Contacts', ['query' => ['where' => 'ContactNumber=="23"']]])
      ->willReturn($response);
    $this->xeroClient->expects($this->at(1))
      ->method('__call')
      ->with('get', ['Contacts', ['query' => ['where' => 'EmailAddress=="mj23@example.com"']]])
      ->willReturn($response);

    $this->xeroClient->expects($this->at(2))
      ->method('__call')
      ->with('put')
      ->willThrowException(new \Exception('Forced error on user creation'));

    $user = User::create($data);
    $user->save();

    $this->runQueueJob();

    $user = User::load(23);
    $this->assertEquals(NULL, $user->get('xero_contact_id')->value);

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/config/system/queues/jobs/xero_contact_sync');
    $this->assertSession()->responseContains('Number of retries: 1');
    $this->assertSession()->responseContains('Remote user matching failed for Michael Jordan.');
  }

}
