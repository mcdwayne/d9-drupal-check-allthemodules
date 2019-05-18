<?php

namespace Drupal\Tests\entity_pilot\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot\Entity\Arrival;
use Drupal\taxonomy\TermInterface;

/**
 * Defines a base class for arrival tests.
 */
abstract class ArrivalTestBase extends EntityPilotTestBase {

  const ARTICLE_UUID = 'de511610-ae97-49a2-b65f-9548e54df2fa';

  /**
   * We use the standard profile so we have the available fields.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Whether to test breadcrumbs.
   *
   * @var bool
   */
  protected $testBreadcrumbs = TRUE;

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
    'administer entity_pilot arrivals',
    'view test entity',
    'edit any article content',
    'edit any page content',
  ];

  /**
   * Tests terms added by arrival.
   *
   * @var bool
   */
  protected $checkTermArrivals = TRUE;

  /**
   * Sets up an arrival for testing.
   *
   * @param int $remote_id
   *   Remote ID.
   * @param array $valid_ids
   *   Valid IDs.
   * @param string $secret
   *   Secret.
   *
   * @return \Drupal\entity_pilot\ArrivalInterface
   *   The created arrival.
   */
  protected function doArrivalCreate($remote_id = 1, array $valid_ids = [1, 2], $secret = 'a22a0b2884fd73c4e211d68e1f031051') {
    $arrival = $this->addAccountsAndArrival($remote_id, $valid_ids, $secret);
    $this->assertUrl('admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/approve');
    $this->assertEqual($arrival->getRemoteId(), $remote_id);
    $this->assertTitle(t('Approve Spring content refresh') . ' | Drupal');
    $this->assertEqual(count($arrival->getPassengers()), 19, '19 passengers on arrival');
    $this->assertText('Vegan');
    $this->assertText('admin');
    $this->assertText('Hidden health benefits of hazelnuts');
    $this->assertText('10 Things to pack for the best holiday');
    $this->assertText('Geography');
    $this->assertLink(t('Preview'));
    $this->assertLink(t('Diff'));

    // View user diff.
    $this->clickLink(t('Diff'));
    $this->assertTitle('View changes | Drupal');
    $this->assertText('Incoming');
    $this->assertText('Existing');

    // Return to edit/approve.
    $this->drupalGet('admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/approve');
    // Check the preview.
    $this->drupalGet('admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/approve/preview/' . self::ARTICLE_UUID);
    $this->assertText('Pickled Schlitz fixie, butcher forage');
    $this->assertText('Submitted by admin');
    $this->assertText('Vegan');
    $this->assertText('Healthy Eating');
    $image = $this->cssSelect('.node .field--name-field-image img');
    $this->assertTrue(preg_match('/hazelnuts-small/', $image[0]->getAttribute('src')));
    return $arrival;
  }

  /**
   * Tests the arrival.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   The arrival to test.
   * @param string $image_field_name
   *   Image field name to test.
   * @param int $remote_id
   *   Int remote ID.
   */
  protected function doArrivalTests(ArrivalInterface $arrival, $image_field_name = 'field_image', $remote_id = 1) {
    \Drupal::entityTypeManager()
      ->getStorage('ep_arrival')
      ->resetCache([$arrival->id()]);
    /* @var \Drupal\entity_pilot\ArrivalInterface $arrival */
    $arrival = Arrival::load($arrival->id());
    $this->assertEqual($arrival->getRemoteId(), $remote_id);
    $this->assertTrue($arrival->isLanded());

    if ($this->checkTermArrivals) {
      // Check that terms exist.
      $tags = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')->loadByProperties([
          'vid' => 'tags',
        ]);
      $this->assertEqual(count($tags), 11);
      $names = array_map(function (TermInterface $tag) {
        return $tag->label();
      }, $tags);
      $this->assertContains('Vegan', $names);
      $this->assertContains('Recipes', $names);
      $this->assertContains('Art', $names);
      $this->assertContains('Nuts', $names);
    }
    // Check that terms exist.
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')->loadByProperties([
        'uid' => 1,
      ]);
    $titles = [
      'About Us',
      '12 Recipes with corn',
      'Hidden health benefits of hazelnuts',
      '10 Things to pack for the best holiday',
    ];
    /* @var \Drupal\node\NodeInterface $node */
    foreach ($nodes as $id => $node) {
      $label = $node->label();
      unset($titles[array_search($label, $titles)]);
      $this->pass("Found node titled $label");
      if ($label !== 'About Us') {
        // Assert file was found.
        $this->assertTrue($node->{$image_field_name}->entity->id());
      }
    }
  }

  /**
   * Adds an account and an arrival.
   *
   * @param int $remote_id
   *   Remote ID.
   * @param array $valid_ids
   *   Valid IDs to expect.
   * @param string $secret
   *   Secret.
   *
   * @return \Drupal\entity_pilot\ArrivalInterface
   *   Created arrival.
   */
  protected function addAccountsAndArrival($remote_id = 1, array $valid_ids = [1, 2], $secret = 'a22a0b2884fd73c4e211d68e1f031051') {
    $this->drupalLogin($this->adminUser);
    // Visit admin structure.
    $this->drupalGet('admin/structure');
    $this->clickLink('Entity Pilot');
    $this->clickLink('Entity Pilot Arrivals');
    $this->assertText('There are no entity pilot arrival entities yet');

    // Add new arrival without an account.
    $this->clickLink('New arrival');

    $this->assertText(t('No Entity Pilot accounts have been created.'));

    // Create an account.
    $this->createAccount('Primary', NULL, NULL, 'Magic ponies fly these skies.');

    // Simulate service outage.
    /* @var MockTransportInterface $transport */
    $transport = \Drupal::service('entity_pilot.transport');
    $transport->setExceptionReturn("stuff wen't bad");

    // Reload.
    $this->drupalGet('admin/structure/entity-pilot/arrivals/add');
    $this->assertRaw(t('An error occurred connecting to the Entity Pilot backend, please try again later or visit the <a href="https://entitypilot.com/status">Entity Pilot status page</a>.'));
    $flights = Json::decode(file_get_contents(__DIR__ . '/Data/flights.json'));
    $flights = array_filter($flights, function (array $flight) use ($valid_ids) {
      return in_array((int) $flight['id'], $valid_ids, TRUE);
    });
    $transport->setQueryReturn(FlightManifest::fromArray($flights, $secret));
    // Verify form is not shown.
    $this->assertNoField('search', 'Did not find search field');

    // Create another account.
    $this->createAccount('Secondary', NULL, NULL, 'The airline of choice for top-shelf goats.');

    // Verify list shown.
    $this->drupalGet('admin/structure/entity-pilot/arrivals/add');
    // Verify form is not shown.
    $this->assertNoField('search', 'Did not find search field');
    // Verify descriptions shown.
    $this->assertText('The airline of choice for top-shelf goats.');
    $this->assertText('Magic ponies fly these skies.');
    $this->assertLink('Secondary');

    // Use the primary account.
    $this->clickLink('Primary');

    if ($this->testBreadcrumbs) {
      $this->assertRaw(t('New Entity Pilot arrival'));
    }

    $assert = $this->assertSession();
    foreach ($valid_ids as $valid_id) {
      $assert->elementExists('css', "input[type=radio][name=remote_id][value={$valid_id}]");
    }
    $assert->buttonExists('Search');
    $this->assertNotNull($this->getSession()
      ->getPage()
      ->find('css', '#edit-go'));

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

    $this->assertText('Spring content refresh');

    $arrivals = \Drupal::entityTypeManager()->getStorage('ep_arrival')->loadByProperties([
      'remote_id' => $remote_id,
    ]);
    $this->assertEqual(1, count($arrivals), 'Arrival was saved');
    /* @var \Drupal\entity_pilot\ArrivalInterface $arrival */
    $arrival = reset($arrivals);
    return $arrival;
  }

}
