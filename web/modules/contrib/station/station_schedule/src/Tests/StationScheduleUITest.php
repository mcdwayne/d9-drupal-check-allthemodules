<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Tests\StationScheduleUITest.
 */

namespace Drupal\station_schedule\Tests;

use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI for station schedules
 *
 * @group station_schedule
 */
class StationScheduleUITest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['station_schedule', 'station_playlist'];

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $programNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
    $this->programNode = Node::create(['type' => 'station_program', 'title' => 'A Program']);
    $this->programNode->save();
  }

  /**
   * @todo.
   */
  public function testCreate() {
    $this->drupalGet('admin/station/schedule');
    $this->clickLink('Add schedule');
    $edit = [
      'title[0][value]' => 'A title',
      'unscheduled_message[0][value]' => "We're on autopilot",
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

}
