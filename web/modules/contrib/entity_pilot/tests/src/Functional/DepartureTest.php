<?php

namespace Drupal\Tests\entity_pilot\Functional;

use Drupal\entity_pilot\Entity\Departure;
use Drupal\entity_pilot\FlightInterface;

/**
 * Ensures that Entity Pilot departure functions work correctly.
 *
 * @group entity_pilot
 */
class DepartureTest extends EntityPilotTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_ui',
    'dynamic_entity_reference',
    'entity_pilot',
    'entity_test',
    'serialization',
    'hal',
    'entity_pilot_test',
    // @todo remove when https://www.drupal.org/node/2308745 lands
    'node',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer entity_pilot accounts',
    'access administration pages',
    'administer entity_pilot departures',
    'view test entity',
  ];

  /**
   * Tests creating and sending a departure.
   */
  public function testDepartures() {
    $this->drupalLogin($this->adminUser);
    // Visit admin structure.
    $this->drupalGet('admin/structure');
    $this->clickLink('Entity Pilot');
    $this->clickLink('Entity Pilot Departures');
    $this->assertText('There are no entity pilot departure entities yet');

    // Add new departure without an account.
    $this->clickLink('New departure');

    $this->assertText(t('No Entity Pilot accounts have been created.'));

    // Create an account.
    $primary_account = $this->createAccount('Primary', NULL, NULL, 'Magic ponies fly these skies.');

    // Reload.
    $this->drupalGet('admin/structure/entity-pilot/departures/add');
    // Verify form is shown.
    $this->assertField('info[0][value]', 'Found description field');

    // Create another account.
    $this->createAccount('Secondary', NULL, NULL, 'The airline of choice for top-shelf goats.');

    // Verify list shown.
    $this->drupalGet('admin/structure/entity-pilot/departures/add');
    // Verify form is not shown.
    $this->assertNoField(('info[0][value]'), 'Did not find description field');
    // Verify descriptions shown.
    $this->assertText('The airline of choice for top-shelf goats.');
    $this->assertText('Magic ponies fly these skies.');
    $this->assertLink('Secondary');

    // Use the primary account.
    $this->clickLink('Primary');

    $this->assertRaw(t('Add departure for %account', ['%account' => $primary_account->label()]));
    $this->assertField('passenger_list[0][target_id]', 'Found passenger field target id');
    $this->assertField('passenger_list[0][target_type]', 'Found passenger field target type');

    // Create some items to reference.
    $item1 = entity_create('entity_test', [
      'name' => 'item1',
    ]);
    $item1->save();
    $item2 = entity_create('entity_test', [
      'name' => 'item2',
    ]);
    $item2->save();
    // Add some extra dynamic entity reference fields.
    $page = $this->getSession()->getPage();
    $button = $page->findButton('passenger_list_add_more');
    $button->click();
    $button->click();

    $edit = [
      'passenger_list[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
      'passenger_list[0][target_type]' => 'user',
      'passenger_list[1][target_id]' => 'item1 (' . $item1->id() . ')',
      'passenger_list[1][target_type]' => 'entity_test',
      'passenger_list[2][target_id]' => 'item2 (' . $item2->id() . ')',
      'passenger_list[2][target_type]' => 'entity_test',
      'info[0][value]' => 'First departure',
      'log' => 'The rain in spain falls mainly on the plain',
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Departure for @type account named %info has been created.', [
      '@type' => 'Primary',
      '%info' => 'First departure',
    ]));

    $this->assertText('First departure');

    $departures = entity_load_multiple_by_properties('ep_departure', [
      'info' => 'First departure',
    ]);
    $this->assertEqual(1, count($departures), 'Departure was saved');
    $departure = reset($departures);
    $this->assertUrl('admin/structure/entity-pilot/departures/' . $departure->id() . '/approve');
    $this->drupalGet('admin/structure/entity-pilot/departures/' . $departure->id());
    $this->assertTitle(t('First departure') . ' | Drupal');
    $this->assertText($this->adminUser->label());
    $this->assertText('item1');
    $this->assertText('item2');

    $this->assertEqual(count($departure->passenger_list), 3, 'Three passengers on departure');
    $this->assertEqual($departure->passenger_list[0]->entity->label(), $this->adminUser->label());
    $this->assertEqual($departure->passenger_list[1]->entity->label(), 'item1');
    $this->assertEqual($departure->passenger_list[2]->entity->label(), 'item2');

    $this->drupalGet('admin/structure/entity-pilot/departures/' . $departure->id() . '/edit');
    $edit = [
      'info[0][value]' => 'Edit departure',
      // Set a log value.
      'log' => $this->randomMachineName(),
      // Remove one child.
      'passenger_list[2][target_id]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('admin/structure/entity-pilot/departures/' . $departure->id());
    $this->assertTitle(t('Edit departure') . ' | Drupal');
    // Reload departure.
    \Drupal::entityManager()->getStorage('ep_departure')->resetCache([$departure->id()]);
    /** @var \Drupal\entity_pilot\DepartureInterface $departure */
    $departure = Departure::load($departure->id());
    $this->assertEqual(count($departure->getPassengers()), 2, 'Two passengers on departure');
    $this->assertEqual($departure->getRevisionLog(), $edit['log']);

    // Approve the departure.
    $this->drupalGet('admin/structure/entity-pilot/departures');
    $this->clickLink(t('Approve'));
    $this->assertText(t('This departure will send the following content to Entity Pilot'));
    $this->assertNotNull($page->findButton('Approve'));
    $this->assertNotNull($page->findButton('Approve & Queue'));

    // Click approve.
    $this->drupalPostForm(NULL, [], t('Approve'));
    $this->assertUrl('admin/structure/entity-pilot/departures');
    $this->assertNoLink(t('Approve'));

    // Queue the departure.
    $this->clickLink(t('Queue'));
    $this->assertText(t('Queue the flight for sending to Entity Pilot on next cron-run'));
    $this->drupalPostForm(NULL, [], t('Queue'));

    // Load the departure and make sure the passengers are still there.
    \Drupal::entityManager()->getStorage('ep_departure')->resetCache([$departure->id()]);
    /** @var \Drupal\entity_pilot\DepartureInterface $departure */
    $departure = Departure::load($departure->id());
    $this->assertEqual(count($departure->getPassengers()), 2, 'Two passengers on departure');
    $this->assertEqual($departure->getRevisionLog(), t('Queued by @name', [
      '@name' => $this->adminUser->getUsername(),
    ]));

    $this->assertNoLink(t('Approve'));
    $this->assertNoLink(t('Queue'));
    $this->assertLink(t('View'));

    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = \Drupal::service('queue')->get('entity_pilot_departures');
    $this->assertEqual($queue->numberOfItems(), 1);

    /** @var \Drupal\entity_pilot\MockTransportInterface $transport */
    $transport = \Drupal::service('entity_pilot.transport');
    $transport->setSendReturn(1);

    $this->container->get('cron')->run();

    $this->assertEqual($queue->numberOfItems(), 0);

    \Drupal::entityManager()->getStorage('ep_departure')->resetCache([$departure->id()]);
    /** @var \Drupal\entity_pilot\DepartureInterface $departure */
    $departure = Departure::load($departure->id());
    $this->assertEqual($departure->getRemoteId(), 1);
    $this->assertTrue($departure->isLanded());
    $flight = $transport->getLastSentFlight();
    $this->assertTrue($flight->getCarrierId(), $departure->getAccount()->getCarrierId());
    $this->assertTrue($flight->getBlackBoxKey(), $departure->getAccount()->getBlackBoxKey());

    // Test deleting the departure.
    $this->drupalPostForm(sprintf('admin/structure/entity-pilot/departures/%d/delete', $departure->id()), [], t('Delete'));
    \Drupal::entityManager()->getStorage('ep_departure')->resetCache([$departure->id()]);
    $departure = Departure::load($departure->id());
    $this->assertFalse($departure);

    // Add another two.
    foreach (['second', 'third'] as $place) {
      // Verify list shown.
      $this->drupalGet('admin/structure/entity-pilot/departures/add');
      $this->clickLink('Primary');

      // Add some extra dynamic entity reference fields.
      $button = $page->findButton('passenger_list_add_more');
      $button->click();
      $button->click();

      $edit = [
        'passenger_list[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
        'passenger_list[0][target_type]' => 'user',
        'passenger_list[1][target_id]' => 'item1 (' . $item1->id() . ')',
        'passenger_list[1][target_type]' => 'entity_test',
        'passenger_list[2][target_id]' => 'item2 (' . $item2->id() . ')',
        'passenger_list[2][target_type]' => 'entity_test',
        'info[0][value]' => $place . ' departure',
        'log' => 'The rain in spain falls mainly on the plain',
      ];

      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('Departure for @type account named %info has been created.', [
        '@type' => 'Primary',
        '%info' => $place . ' departure',
      ]));

      // Approve the departure.
      $this->drupalGet('admin/structure/entity-pilot/departures');
      $this->clickLink(t('Approve'));
      $this->assertText(t('This departure will send the following content to Entity Pilot'));
      // Simulate over-quota.
      $transport->setExceptionReturn('You are over your monthly quota, which resets on 12-05-2015. Alternatively visit <a href="https://entitypilot.com">Entity Pilot</a> and choose an alternate plan.');
      // Click approve.
      $this->drupalPostForm(NULL, [], t('Approve & Send'));
      // Should fail with quota exceeded.
      $this->checkForMetaRefresh();
      $this->assertText('You are over your monthly quota');
      // Approve the departure.
      $transport->setSendReturn(1);
      // Clear the exception.
      $transport->setExceptionReturn(NULL);
      $this->drupalGet('admin/structure/entity-pilot/departures');
      $this->clickLink(t('Approve'));
      $this->assertText(t('This departure will send the following content to Entity Pilot'));
      // Click approve.
      $this->drupalPostForm(NULL, [], t('Approve & Send'));
      $this->checkForMetaRefresh();
      $this->assertUrl('admin/structure/entity-pilot/departures');
      $this->assertNoLink(t('Approve'));
      $departures = entity_load_multiple_by_properties('ep_departure', [
        'info' => $place . ' departure',
      ]);
      $this->assertEqual(1, count($departures), 'Departure was saved');
      /** @var \Drupal\entity_pilot\DepartureInterface $departure */
      $departure = reset($departures);
      $this->assertEqual($departure->getStatus(), FlightInterface::STATUS_LANDED);
    }
  }

}
