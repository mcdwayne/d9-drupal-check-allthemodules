<?php
/**
 * @file
 * Contains \Drupal\merci\Tests\MerciTest.
 */

namespace Drupal\merci\Tests;

use Drupal\merci\MerciTestBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Tests\TaxonomyTestTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tests pager functionality.
 *
 * @group Merci
 */
class MerciTest extends MerciTestBase {

  public $resource1;
  public $office_hours;
  public $merci_user;
  public $merci_role;

  /**
   * Modules to enable.
   *
   * @var array
   */
  function setUp() {
    // Enable the module.
    parent::setUp();


    // Create merci user. 
    $this->merci_user = $this->createUser(array(
      'view all revisions',
      'revert all revisions',
      'edit own merci_resource content',
      'delete own merci_resource content',
      'access merci line item overview',
      'add merci line item entities',
      'edit merci line item entities',
      'delete merci line item entities',
      'view all merci line item revisions',
    ));

    $roles = $this->merci_user->getRoles();
    $this->merci_role = reset($roles);

    $vocabulary = Vocabulary::load('resource_tree');

    $values = array(
      'field_merci_allow_overnight' => array(
        'value' => TRUE,
      ),
      'field_merci_allow_weekends' => array(
        'value' => TRUE,
      ),
      'field_required_roles' => array(
        'target_id' => $this->merci_role,
      ),
    );

    $this->term = $this->createTerm($vocabulary, $values);

    $settings = array(
      'type' => 'office_hours',
      'field_office_hours' => array(
        array(
          'day' => 0,
          'starthours' => '400',
          'endhours' => '1000',
        ),
        array(
          'day' => 0,
          'starthours' => '1400',
          'endhours' => '1800',
        ),
        array(
          'day' => 1,
          'starthours' => '400',
          'endhours' => '1800',
        ),
      ),
    );

    $this->office_hours = $this->createNode($settings);

    $id = $this->office_hours->id();
    $this->drupalGet("/node/$id/edit");
    $settings = array(
      'type' => 'merci_resource',
      'field_merci_grouping' => array(
        'target_id' => $this->term->id(),
      ),
      'field_merci_location' => array(
        'target_id' => $this->office_hours->id(),
      ),
    );
    $this->resource1 = $this->createNode($settings);
  }

/*
  function testMerciMaxLength() {

    $vocabulary = Vocabulary::load('resource_tree');

    $values = array(
      'field_merci_allow_overnight' => array(
        'value' => TRUE,
      ),
      'field_merci_allow_weekends' => array(
        'value' => TRUE,
      ),
      'field_required_roles' => array(
        'target_id' => $this->merci_role,
      ),
      'field_max_length_of_reservation' => array(
        'interval' => 1,
        'period' => "day",
      ),
    );

    $term = $this->createTerm($vocabulary, $values);

    $settings = array(
      'type' => 'merci_resource',
      'field_merci_grouping' => array(
        'target_id' => $term->id(),
      ),
      'field_merci_location' => array(
        'target_id' => $this->office_hours->id(),
      ),
    );
    $resource = $this->createNode($settings);

    // Allow overnight should fail.
    $settings = array(
      'merci_reservation_items' => array(
        'target_id' => $resource->id(),
      ),
    );

    $node = $this->merciCreateReservation("Sunday 9am", "Monday 3pm", $settings);
    $violations = $node->validate();
    $this->assertEqual($violations->get(0)->getMessage()->render(), "Item cannot be reserved for longer than 1 day.");

    // Max length is okay.
    $node = $this->merciCreateReservation("Sunday 9am", "Sunday 3pm");
    $violations = $node->validate();
    $this->assertFalse($violations->has(0));
  }

  function testMerciAllowOvernight() {

    // Allow overnight is okay.
    $node = $this->merciCreateReservation("Sunday 9am", "Sunday 3pm");
    $violations = $node->validate();
    $this->assertFalse($violations->has(0));

    $vocabulary = Vocabulary::load('resource_tree');

    $values = array(
      'field_merci_allow_overnight' => array(
        'value' => FALSE,
      ),
      'field_merci_allow_weekends' => array(
        'value' => TRUE,
      ),
      'field_required_roles' => array(
        'target_id' => $this->merci_role,
      ),
    );

    $term = $this->createTerm($vocabulary, $values);

    $settings = array(
      'type' => 'merci_resource',
      'field_merci_grouping' => array(
        'target_id' => $term->id(),
      ),
      'field_merci_location' => array(
        'target_id' => $this->office_hours->id(),
      ),
    );
    $resource = $this->createNode($settings);

    // Allow overnight should fail.
    $settings = array(
      'merci_reservation_items' => array(
        'target_id' => $resource->id(),
      ),
    );

    $node = $this->merciCreateReservation("Sunday 9am", "Monday 3pm", $settings);
    $violations = $node->validate();
    $this->assertEqual($violations->get(0)->getMessage()->render(), "Reservation can not go overnight.");
  }

  function testMerciOpenHours() {

    // Sunday 4am-10am, 2pm-6pm
    // Monday 4am-6pm
    
    // Test open.
    //
    $node = $this->merciCreateReservation("Sunday 9am", "Sunday 3pm");
    $violations = $node->validate();
    $this->assertFalse($violations->has(0));

    // Test start is too early.
    $node = $this->merciCreateReservation("Sunday 3am", "Sunday 3pm");
    $violations = $node->validate();
    if (strpos($violations->get(0)->getMessage()->render(), 'Reservation begins at a time we are closed.') === FALSE) {
      $this->assert('fail', ' Test start is too early. Did not validate.');
    }

    // Test start is during lunch.
    $node = $this->merciCreateReservation("Sunday 12pm", "Sunday 3pm");
    $violations = $node->validate();
    if (strpos($violations->get(0)->getMessage()->render(), 'Reservation begins at a time we are closed.') === FALSE) {
      $this->assert('fail', ' Test start is during lunch. Did not validate.');
    }

    // Test start is after close.
    $node = $this->merciCreateReservation("Sunday 7pm", "Sunday 8pm");
    $violations = $node->validate();
    if (strpos($violations->get(0)->getMessage()->render(), 'Reservation begins at a time we are closed.') === FALSE) {
      $this->assert('fail', ' Test start is after close. Did not validate.');
    }

    // Test end is too early.
    $node = $this->merciCreateReservation("Sunday 4pm", "Monday 3am");
    $violations = $node->validate();
    if (strpos($violations->get(0)->getMessage()->render(), 'Reservation ends at a time we are closed.') === FALSE) {
      $this->assert('fail', ' Test end is too early. Did not validate.');
    }

    // Test end is during lunch.
    $node = $this->merciCreateReservation("Sunday 8am", "Sunday 1pm");
    $violations = $node->validate();
    if (strpos($violations->get(0)->getMessage()->render(), 'Reservation ends at a time we are closed.') === FALSE) {
      $this->assert('fail', ' Test end is during lunch. Did not validate.');
    }

    // Test end is after close.
    $node = $this->merciCreateReservation("Sunday 4pm", "Sunday 8pm");
    $violations = $node->validate();
    if (strpos($violations->get(0)->getMessage()->render(), 'Reservation ends at a time we are closed.') === FALSE) {
      $this->assert('fail', ' Test end is after close. Did not validate.');
    }

  }

  function testMerciNoConflictResources() {

    // Login the merci user.
    $this->drupalLogin($this->merci_user);

    $node = $this->merciCreateReservation("Sunday 3pm", "Sunday 4pm");
    $node->save();

    // Should pass.
    $node2 = $this->merciCreateReservation("Monday 9am", "Monday 10am");
    $violations = $node2->validate();
    $this->assertFalse($violations->has(0));

  }

 */
  function testMerciTooManyPlusConflictResources() {

    // Login the merci user.
    $this->drupalLogin($this->merci_user);

    $node = $this->merciCreateReservation("Sunday 3pm", "Sunday 4pm");
    $node->save();

    // Should fail.
    $node = $this->merciCreateReservation("Sunday 3pm", "Sunday 4pm");
    $violations = $node->validate();
    $pattern = '@is already reserved by:@';
    $message = "There is a conflict";
    $this->assert((bool) preg_match($pattern, $violations->get(0)->getMessage()->render()), $message);

  }

  /**
   * Check quantity field logic.
   */
  function testMerciQuantityResources() {

    $settings = array(
      'type' => 'merci_resource',
      'field_merci_grouping' => array(
        'target_id' => $this->term->id(),
      ),
      'field_merci_location' => array(
        'target_id' => $this->office_hours->id(),
      ),
      'field_reservable_quantity' => 2,
    );
    $resource = $this->createNode($settings);
    $this->drupalGet("/node/" . $resource->id() . "/edit");

    // Login the merci user.
    $this->drupalLogin($this->merci_user);

    // Reserve one of the item.
    $settings = array(
      'field_quantity' => 2,
      'merci_reservation_items' => array(
        'target_id' => $resource->id(),
      ),
    );

    $node = $this->merciCreateReservation("Sunday 3pm", "Sunday 4pm", $settings);
    $violations = $node->validate();
    $this->assertFalse($violations->has(0));

    // Reserve two of the item.
    $settings = array(
      'field_quantity' => 3,
      'merci_reservation_items' => array(
        'target_id' => $resource->id(),
      ),
    );

    $node = $this->merciCreateReservation("Sunday 3pm", "Sunday 4pm", $settings);
    $violations = $node->validate();
    $this->assert(TRUE, $violations->count());
    $pattern = '@You have selected too many of the same item@';
    $this->assert((bool) preg_match($pattern, $violations->get(0)->getMessage()->render()), $violations->get(0)->getMessage()->render());

    // Reserve one of the item.
    $settings = array(
      'field_quantity' => 1,
      'merci_reservation_items' => array(
        'target_id' => $resource->id(),
      ),
    );

    $node = $this->merciCreateReservation("Sunday 3pm", "Sunday 4pm", $settings);
    $violations = $node->validate();
    $this->assertFalse($violations->has(0));
    $node->save();

    // Reserve one of the item.
    $settings = array(
      'field_quantity' => 1,
      'merci_reservation_items' => array(
        'target_id' => $resource->id(),
      ),
    );

    $node = $this->merciCreateReservation("Sunday 3pm", "Sunday 4pm", $settings);
    $violations = $node->validate();
    $this->assertFalse($violations->has(0));

    // Reserve two of the item.
    $settings = array(
      'field_quantity' => 2,
      'merci_reservation_items' => array(
        'target_id' => $resource->id(),
      ),
    );

    $node = $this->merciCreateReservation("Sunday 3pm", "Sunday 4pm", $settings);
    $violations = $node->validate();
    $pattern = '@is already reserved by:@';
    $this->assert((bool) preg_match($pattern, $violations->get(0)->getMessage()->render()), $violations->get(0)->getMessage()->render());

    // Reserve two of the item.
    $settings = array(
      'field_quantity' => 2,
      'merci_reservation_items' => array(
        'target_id' => $resource->id(),
      ),
    );

    $node = $this->merciCreateReservation("Monday 3pm", "Monday 4pm", $settings);
    $violations = $node->validate();
    $this->assertFalse($violations->has(0));
    $node->save();
    $this->drupalGet("/admin/structure/merci_line_item/" . $node->id() . "/edit");

    // Should fail.
    // Reserve one of the item.
    $settings = array(
      'field_quantity' => 1,
      'merci_reservation_items' => array(
        'target_id' => $resource->id(),
      ),
    );
    $node = $this->merciCreateReservation("Monday 3pm", "Monday 4pm", $settings);
    $violations = $node->validate();
    $node->save();
    $this->drupalGet("/admin/structure/merci_line_item/" . $node->id() . "/edit");
    $pattern = '@is already reserved by:@';
    $message = "There is a conflict";
    $this->assert((bool) preg_match($pattern, $violations->get(0)->getMessage()->render()), $message);
  }

}
