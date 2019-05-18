<?php

namespace Drupal\Tests\entity_pilot\Functional;

/**
 * Ensures that Entity Pilot arrival functions work correctly.
 *
 * @group entity_pilot
 */
class ArrivalsApproveAndLandTest extends ArrivalTestBase {

  /**
   * Data provider for testArrivalLand.
   *
   * @return array
   *   Test cases.
   */
  public function providerRemoteIds() {
    return [
      '1.x encryption format' => [1, [1, 2]],
      '2.x encryption format' => [
        3,
        [3],
        'def00000cc8d032c4b4c13cb18c6760dd486497ab9bb054e0674e567ef772611938503bde6cfb17c3d89a60c413cacb8137239459187b2b44109f4fc1b03e9205184cd15',
      ],
    ];
  }

  /**
   * Tests creating an landing an arrival and importing via UI.
   *
   * @dataProvider providerRemoteIds
   */
  public function testArrivalLand($remote_id, $valid_ids, $secret = 'a22a0b2884fd73c4e211d68e1f031051') {
    $arrival = $this->doArrivalCreate($remote_id, $valid_ids, $secret);
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
      'link_departure' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Approve & Land'));
    $this->checkForMetaRefresh();
    $this->assertText('Arrival for Primary account named Spring content refresh has been updated.');
    // Reload arrival.
    /** @var \Drupal\entity_pilot\ArrivalStorageInterface $arrival_storage */
    $entityTypeManager = \Drupal::entityTypeManager();
    $arrival_storage = $entityTypeManager->getStorage('ep_arrival');
    $arrival = $arrival_storage->resetCacheAndLoad($arrival->id());
    $this->assertTrue($arrival->hasLinkedDeparture());
    $departure = $arrival->getLinkedDeparture();
    $approved = $arrival->getApproved();
    $departure_passengers = array_keys($departure->getPassengers());
    $this->assertTrue(empty(array_diff($departure_passengers, $approved)));
    $this->assertTrue($arrival->isLanded());
    $this->assertEqual($arrival->getRemoteId(), $remote_id);
    $this->assertNoLink(t('Approve'));
    $this->assertNoLink(t('Queue'));
    $this->assertLink(t('View'));

    $this->doArrivalTests($arrival, 'field_image', $remote_id);

    // Test two-way syncing.
    // Check that terms exist.
    $nodeStorage = $entityTypeManager->getStorage('node');
    $nodes = $nodeStorage->loadByProperties([
      'uid' => 1,
      'title' => '12 Recipes with corn',
    ]);
    $node = reset($nodes);
    $this->drupalPostForm('node/' . $node->id() . '/edit', [
      'title[0][value]' => '12 recipes without corn',
      'field_image[0][alt]' => 'alt tag me sir',
    ], t('Save'));
    $updated = $nodeStorage->loadUnchanged($node->id());
    $this->assertEqual($updated->label(), '12 recipes without corn');
    // Verify list shown.
    $this->drupalGet('admin/structure/entity-pilot/arrivals/add/primary');
    $edit = [
      'remote_id' => $remote_id,
      'log' => 'The rain in spain falls mainly on the plain',
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->checkForMetaRefresh();
    $this->assertRaw(t('Arrival for @type account named %info has been created.', [
      '@type' => 'Primary',
      '%info' => 'Spring content refresh',
    ]));

    $arrivals = $arrival_storage->loadByProperties([
      'remote_id' => $remote_id,
    ]);
    $arrival = end($arrivals);
    $this->assertUrl('admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/approve');
    // Approval only the corn story bar the admin account.
    $edit = [
      'approved_passengers[8384692b-379c-4067-b000-bea20ef3aaca]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Approve & Land'));
    $this->checkForMetaRefresh();
    $this->assertText('Arrival for Primary account named Spring content refresh has been updated.');
    // Check the name updated again.
    $updated = $nodeStorage->loadUnchanged($node->id());
    $this->assertEqual($updated->label(), '12 Recipes with corn');
  }

}
