<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointGroupAPITest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointsTestBase;
use Drupal\breakpoint\BreakpointGroup;
use Drupal\breakpoint\Breakpoint;
use Drupal\breakpoint\InvalidBreakpointNameException;
use Drupal\breakpoint\InvalidBreakpointSourceException;
use Drupal\breakpoint\InvalidBreakpointSourceTypeException;

/**
 * Tests for general breakpoint group API functions.
 */
class BreakpointGroupAPITest extends BreakpointGroupTestBase {

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Breakpoint group general API functions',
      'description' => 'Test general API functions of the breakpoint module.',
      'group' => 'Breakpoint',
    );
  }

  /**
   * Test Breakpoint::buildConfigName().
   */
  public function testConfigName() {
    // Try an invalid sourceType.
    $breakpoint_group = entity_create('breakpoint_group', array(
      'label' => drupal_strtolower($this->randomName()),
      'source' => 'custom_module',
      'sourceType' => 'oops',
    ));

    $exception = FALSE;
    try {
      $breakpoint_group->save();
    }
    catch (InvalidBreakpointSourceTypeException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('An exception is thrown when an invalid sourceType is entered.'));

    // Try an invalid source.
    $breakpoint_group->id = '';
    $breakpoint_group->sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;
    $breakpoint_group->source = 'custom*_module source';

    $exception = FALSE;
    try {
      $breakpoint_group->save();
    }
    catch (InvalidBreakpointSourceException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('An exception is thrown when an invalid source is entered.'));

    // Try a valid breakpoint_group.
    $breakpoint_group->id = 'test';
    $breakpoint_group->source = 'custom_module_source';

    $exception = FALSE;
    try {
      $breakpoint_group->save();
    }
    catch (\Exception $e) {
      $exception = TRUE;
    }
    $this->assertFalse($exception, t('No exception is thrown when a valid data is passed.'));
  }
}
