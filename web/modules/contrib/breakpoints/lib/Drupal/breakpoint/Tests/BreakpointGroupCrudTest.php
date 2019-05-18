<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointGroupCrudTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointGroupTestBase;
use Drupal\breakpoint\BreakpointGroup;
use Drupal\breakpoint\Breakpoint;

/**
 * Tests for breakpoint group CRUD operations.
 */
class BreakpointGroupCrudTest extends BreakpointGroupTestBase {

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Breakpoint group CRUD operations',
      'description' => 'Test creation, loading, updating, deleting of breakpoint groups.',
      'group' => 'Breakpoint',
    );
  }

  /**
   * Test CRUD operations for breakpoint groups.
   */
  public function testBreakpointGroupCrud() {
    // Add breakpoints.
    $breakpoints = array();
    for ($i = 0; $i <= 3; $i++) {
      $width = ($i + 1) * 200;
      $breakpoint = entity_create('breakpoint', array(
        'name' => drupal_strtolower($this->randomName()),
        'weight' => $i,
        'mediaQuery' => "(min-width: {$width}px)",
      ));
      $breakpoint->save();
      $breakpoints[$breakpoint->id()] = $breakpoint;
    }
    // Add a breakpoint group with minimum data only.
    $label = $this->randomName();

    $group = entity_create('breakpoint_group', array(
      'label' => $label,
      'id' => drupal_strtolower($label),
    ));
    $group->save();
    $this->verifyBreakpointGroup($group);

    // Update the breakpoint group.
    $group->breakpoints = array_keys($breakpoints);
    $group->save();
    $this->verifyBreakpointGroup($group);

    // Duplicate the breakpoint group.
    $new_set = entity_create('breakpoint_group', array(
      'breakpoints' => $group->breakpoints,
    ));
    $duplicated_set = $group->duplicate();
    $this->verifyBreakpointGroup($duplicated_set, $new_set);

    // Delete the breakpoint group.
    $group->delete();
    $this->assertFalse(entity_load('breakpoint_group', $group->id), t('breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));
  }
}
