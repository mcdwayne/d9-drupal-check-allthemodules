<?php

namespace Drupal\Tests\entity_pilot\Functional;

use Drupal\entity_pilot\Entity\Arrival;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Ensures that Entity Pilot arrival functions work correctly on queue.
 *
 * @group entity_pilot
 */
class ArrivalsApproveAndQueueTest extends ArrivalTestBase {

  use CronRunTrait;

  /**
   * Tests creating an landing an arrival and importing on cron.
   */
  public function testArrivalsQueue() {
    $arrival = $this->doArrivalCreate();
    // Return to edit/approve.
    $this->drupalGet('admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/approve');
    // Approval all bar the admin account.
    $edit = [
      'approved_passengers[5f1af923-22f8-4799-9204-4f6f030bd879]' => 1,
      'approved_passengers[55ad425f-7832-4ff6-bb02-2f688bf95847]' => 1,
      'approved_passengers[cd215df5-242c-4844-a901-1dd566874727]' => 1,
      'approved_passengers[df0064ab-a7e1-4f30-8f06-6bb03032e052]' => 1,
      'approved_passengers[de511610-ae97-49a2-b65f-9548e54df2fa]' => 1,
      'approved_passengers[ea15274d-949c-4238-902d-45ca3c828ed1]' => 1,
      'approved_passengers[9887a6f6-23a8-4080-8231-49804054f681]' => 1,
      'approved_passengers[721b1351-a98f-4daa-842d-455c641fecbf]' => 1,
      'approved_passengers[19a8da05-5d5a-424d-8d00-e775744346ea]' => 1,
      'approved_passengers[51bad7d9-3994-4745-9220-32cb4f26c8e2]' => 1,
      'approved_passengers[01f1b727-d660-4647-8439-57be4e9cfce7]' => 1,
      'approved_passengers[7bec3ab2-cc87-488e-a607-7d70fb243e5f]' => 1,
      'approved_passengers[82c0651e-9bf9-4de7-9800-be1d6a5ae5a4]' => 1,
      'approved_passengers[ece8e4b2-737d-4818-af00-bea9078d2103]' => 1,
      'approved_passengers[ebac96dd-8b05-4abf-b301-1a1b3abe365c]' => 1,
      'approved_passengers[8798094b-9eaf-48e0-894a-74bb296d2f1f]' => 1,
      'approved_passengers[db099a7a-fc42-4765-8900-d233aa514a6b]' => 1,
      'approved_passengers[8384692b-379c-4067-b000-bea20ef3aaca]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Approve'));
    $this->assertText('Arrival for Primary account named Spring content refresh has been updated.');
    $this->assertLink(t('Queue'));
    $this->assertNoLink(t('Approve'));
    // Reload arrival.
    \Drupal::entityManager()
      ->getStorage('ep_arrival')
      ->resetCache([$arrival->id()]);
    /* @var \Drupal\entity_pilot\ArrivalInterface $arrival */
    $arrival = Arrival::load($arrival->id());
    $this->assertEqual($arrival->getRemoteId(), 1);
    $this->assertEqual(count($arrival->getApproved()), 18, 'Eighteen approved passengers on arrival');
    $this->drupalGet('admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/queue');
    $this->drupalPostForm(NULL, [], t('Queue'));
    // Reload arrival.
    \Drupal::entityManager()->getStorage('ep_arrival')->resetCache([$arrival->id()]);
    /* @var \Drupal\entity_pilot\ArrivalInterface $arrival */
    $arrival = Arrival::load($arrival->id());
    $this->assertTrue($arrival->isQueued());
    $this->assertEqual($arrival->getRemoteId(), 1);
    $this->assertNoLink(t('Approve'));
    $this->assertNoLink(t('Queue'));
    $this->assertLink(t('View'));

    /* @var QueueInterface $queue */
    $queue = \Drupal::service('queue')->get('entity_pilot_arrivals');
    $this->assertEqual($queue->numberOfItems(), 1);

    $this->cronRun();

    $this->assertEqual($queue->numberOfItems(), 0);

    $this->doArrivalTests($arrival);
  }

}
