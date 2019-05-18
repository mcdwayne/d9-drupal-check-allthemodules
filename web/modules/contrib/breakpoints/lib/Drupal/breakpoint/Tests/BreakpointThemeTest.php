<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointsThemeTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointGroupTestBase;
use Drupal\breakpoint\BreakpointGroup;
use Drupal\breakpoint\Breakpoint;

/**
 * Test breakpoints provided by themes.
 */
class BreakpointThemeTest extends BreakpointGroupTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoint_theme_test');

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Breakpoint theme functionality',
      'description' => 'Thoroughly test the breakpoints provided by a theme.',
      'group' => 'Breakpoint',
    );
  }

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  public function setUp() {
    parent::setUp();
    theme_enable(array('breakpoint_test_theme'));
  }

  /**
   * Test the breakpoints provided by a theme.
   */
  public function testThemeBreakpoints() {
    // Verify the breakpoint group for breakpoint_test_theme was created.
    $breakpoint_group_obj = entity_create('breakpoint_group', array(
      'label' => 'Breakpoint test theme',
      'id' => 'breakpoint_test_theme',
      'sourceType' => Breakpoint::SOURCE_TYPE_THEME,
      'overridden' => FALSE,
    ));
    $breakpoint_group_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array(),
      'theme.breakpoint_test_theme.narrow' => array(),
      'theme.breakpoint_test_theme.wide' => array(),
      'theme.breakpoint_test_theme.tv' => array(),
    );

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Override the breakpoints.
    $overridden_set = clone $breakpoint_group_obj;
    $breakpoint_group = entity_load('breakpoint_group', 'breakpoint_test_theme');
    $breakpoint_group = $breakpoint_group->override();

    $overridden_set->overridden = 1;
    $this->verifyBreakpointGroup($overridden_set);

    // Verify the breakpoints in this breakpoint group are overridden.
    foreach (array_keys($breakpoint_group_obj->breakpoints) as $breakpoint_id) {
      $breakpoint = entity_load('breakpoint', $breakpoint_id);
      $this->assertTrue($breakpoint->overridden, t('Breakpoint @breakpoint should be overridden', array('@breakpoint' => $breakpoint->label())), t('Breakpoint API'));
    }

    // Revert the breakpoint group.
    $breakpoint_group = entity_load('breakpoint_group', 'breakpoint_test_theme');
    $breakpoint_group = $breakpoint_group->revert();

    // Verify the breakpoint group has its original values again when loaded.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Verify the breakpoints in this breakpoint group are no longer overridden.
    foreach (array_keys($breakpoint_group_obj->breakpoints) as $breakpoint_id) {
      $breakpoint = entity_load('breakpoint', $breakpoint_id);
      $this->assertFalse($breakpoint->overridden, t('Breakpoint @breakpoint should not be overridden', array('@breakpoint' => $breakpoint->label())), t('Breakpoint API'));
    }

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertFalse(entity_load('breakpoint_group', 'breakpoint_test_theme'), t('breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));
  }

  /**
   * Test the breakpoints defined by the custom group.
   */
  public function testThemeBreakpointGroup() {
    // Verify the breakpoint group 'test' was created by breakpoint_test_theme.
    $breakpoint_group_obj = entity_create('breakpoint_group', array(
      'label' => 'Test',
      'id' => 'test',
      'sourceType' => Breakpoint::SOURCE_TYPE_THEME,
      'source' => 'breakpoint_test_theme',
      'overridden' => FALSE,
    ));
    $breakpoint_group_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array('1.5x', '2.x'),
      'theme.breakpoint_test_theme.narrow' => array(),
      'theme.breakpoint_test_theme.wide' => array(),
    );

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertFalse(entity_load('breakpoint_group', 'test'), t('breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));
  }

  /**
   * Test the breakpoints defined by the custom group in the module.
   */
  public function testThemeBreakpointGroupModule() {
    // Call the import manually, since the testbot needs to enable the module
    // first, otherwise the theme isn't detected.
    _breakpoint_import_breakpoint_groups('breakpoint_theme_test', Breakpoint::SOURCE_TYPE_MODULE);

    // Verify the breakpoint group 'module_test' was created by
    // breakpoint_theme_test module.
    $breakpoint_group_obj = entity_create('breakpoint_group', array(
      'label' => 'Test Module',
      'id' => 'module_test',
      'sourceType' => Breakpoint::SOURCE_TYPE_MODULE,
      'source' => 'breakpoint_theme_test',
      'overridden' => FALSE,
    ));
    $breakpoint_group_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array(),
      'theme.breakpoint_test_theme.narrow' => array(),
      'theme.breakpoint_test_theme.wide' => array(),
    );

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Override the breakpoints.
    $overridden_set = clone $breakpoint_group_obj;
    $breakpoint_group = entity_load('breakpoint_group', 'module_test');
    $breakpoint_group = $breakpoint_group->override();

    $overridden_set->overridden = 1;
    $this->verifyBreakpointGroup($overridden_set);

    // This group uses breakpoints defined by an other theme. This means the
    // breakpoints should *not* be overridden.
    foreach (array_keys($breakpoint_group_obj->breakpoints) as $breakpoint_id) {
      $breakpoint = entity_load('breakpoint', $breakpoint_id, TRUE);
      $this->assertFalse($breakpoint->overridden, t('Breakpoint @breakpoint should not be overridden.', array('@breakpoint' => $breakpoint->label())), t('Breakpoint API'));
    }

    // Revert the breakpoint group.
    $breakpoint_group = entity_load('breakpoint_group', 'module_test');
    $breakpoint_group = $breakpoint_group->revert();
    $this->verbose(highlight_string("<?php \n// Object\n" . var_export($breakpoint_group_obj, TRUE), TRUE));
    $this->verbose(highlight_string("<?php \n// Group\n" . var_export($breakpoint_group, TRUE), TRUE));

    // Verify the breakpoint group has its original values again when loaded.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Verify the breakpoints in this breakpoint group are not overridden.
    foreach (array_keys($breakpoint_group_obj->breakpoints) as $breakpoint_id) {
      $breakpoint = entity_load('breakpoint', $breakpoint_id);
      $this->assertFalse($breakpoint->overridden, t('Breakpoint @breakpoint should not be overridden.', array('@breakpoint' => $breakpoint->label())), t('Breakpoint API'));
    }

    // Disable the test theme and verify the breakpoint group still exists.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertTrue(entity_load('breakpoint_group', 'module_test'), 'Breakpoint group still exists if theme is disabled.');

    // Disable the test module and verify the breakpoint group still exists.
    module_disable(array('breakpoint_theme_test'));
    $this->assertTrue(entity_load('breakpoint_group', 'module_test'), 'Breakpoint group still exists if module is disabled.');

    // Uninstall the test module and verify the breakpoint group is deleted.
    module_uninstall(array('breakpoint_theme_test'));
    $this->assertFalse(entity_load('breakpoint_group', 'module_test'), 'Breakpoint group is removed if module is uninstalled.');
  }

}
