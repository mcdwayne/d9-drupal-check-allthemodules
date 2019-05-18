<?php

namespace Drupal\Tests\gridstack\Kernel;

use Drupal\gridstack\Entity\GridStack;
use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;

/**
 * Tests creation, loading, updating, deleting of GridStack optionsets.
 *
 * @coversDefaultClass \Drupal\gridstack\Entity\GridStack
 *
 * @group gridstack
 */
class GridStackCrudTest extends BlazyKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'image',
    'blazy',
    'gridstack',
    'gridstack_ui',
    'gridstack_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->installEntitySchema('gridstack');

    // Enable Bootstrap support.
    $this->blazyManager->getConfigFactory()->getEditable('gridstack.settings')->set('framework', 'bootstrap')->save();
  }

  /**
   * Tests CRUD operations for GridStack optionsets.
   */
  public function testGridStackCrud() {
    // Add a GridStack optionset with minimum data only.
    $empty = GridStack::create([
      'name'  => 'test_empty',
      'label' => 'Empty gridstack',
      'json'  => [],
    ]);

    $empty->save();
    $this->verifyGridStackOptionset($empty);

    // Add main GridStack optionset with possible properties.
    $main = GridStack::create([
      'name'  => 'test_main',
      'label' => 'Test main',
      'json'  => [],
    ]);

    $main->save();

    $settings = [
      'cellHeight'     => 80,
      'minWidth'       => 480,
      'verticalMargin' => 15,
    ] + $main->getSettings();

    $main->setSettings($settings);

    $main->getSetting('float');
    $main->setSetting('float', TRUE);

    $main->getOption('use_framework');
    $main->setOption('use_framework', TRUE);

    $main->save();

    $this->assertEquals(TRUE, $main->getSetting('float'));
    $this->assertEquals(TRUE, $main->getOption('use_framework'));

    $this->verifyGridStackOptionset($main);

    // Alter some gridstack optionset properties and save again.
    $main->set('label', 'Altered gridstack');
    $main->setOption('use_framework', TRUE);
    $main->save();
    $this->verifyGridStackOptionset($main);

    // Disable auto and save again.
    $main->setSetting('auto', FALSE);
    $main->save();
    $this->verifyGridStackOptionset($main);

    // Delete the gridstack optionset.
    $main->delete();

    $gridstacks = GridStack::loadMultiple();
    $this->assertFalse(isset($gridstacks[$main->id()]), 'GridStack::loadMultiple: Deleted gridstack optionset no longer exists.');

    // Tests for optionset Frontend JS.
    $frontend = GridStack::load('frontend');

    $icon_uri = $frontend->getIconUri();
    $this->assertTrue(strpos($icon_uri, 'frontend.png') !== FALSE);

    $icon_url = $frontend->getIconUrl();
    $this->assertTrue(strpos($icon_url, 'frontend.png') !== FALSE);

    $string = 'data-role|complimentary';
    $attributes = $frontend->parseAttributes($string);
    $this->assertEquals('complimentary', $attributes['data-role']);

    $breakpoints = $frontend->getJson('breakpoints');
    $this->assertTrue(is_string($breakpoints));

    $end = $frontend->getEndBreakpointGrids('grids');
    $this->assertEquals(0, $end[0]['x']);

    $lg = $frontend->getBreakpoints('lg');
    $this->assertEquals(12, $lg['column']);

    $grid = $frontend->getBreakpointGrid('lg', 0, 'x', 'grids');
    $this->assertEquals(0, $grid);

    $auto = $frontend->getOptions(['settings', 'float']);
    $this->assertEquals(FALSE, $auto);

    $options = $frontend->getOptions();
    $this->assertArrayHasKey('settings', $options);

    $summary = $frontend->getJsonSummaryBreakpoints('lg', FALSE, TRUE);
    $this->assertTrue(strpos($summary, 'width') === FALSE);

    $settings = [];
    $frontend->gridsJsonToArray($settings);
    $this->assertArrayHasKey('breakpoints', $settings);

    $widths = $frontend->optimizeGridWidths($settings, 'grids', FALSE);
    $this->assertNotEmpty($widths);

    // Tests for optionset Test which is a clone of Bootstrap.
    $bootstrap = GridStack::load('test');
    $nested = $bootstrap->getNestedGridsByDelta(1);
    $this->assertNotEmpty($nested);

    $settings = [];
    $bootstrap->gridsJsonToArray($settings);
    $this->assertArrayHasKey('breakpoints', $settings);

    $settings['delta'] = 1;
    $settings['nested_delta'] = 1;
    $widths = $bootstrap->optimizeGridWidths($settings, 'nested', FALSE);
    $this->assertArrayHasKey('lg', $widths);
  }

  /**
   * Verifies that a gridstack optionset is properly stored.
   *
   * @param \Drupal\gridstack\Entity\GridStack $gridstack
   *   The GridStack instance.
   */
  public function verifyGridStackOptionset(GridStack $gridstack) {
    $t_args = ['%gridstack' => $gridstack->label()];
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Verify the loaded gridstack has all properties.
    $gridstack = GridStack::load($gridstack->id());
    $this->assertEquals($gridstack->id(), $gridstack->id(), format_string('GridStack::load: Proper gridstack id for gridstack optionset %gridstack.', $t_args));
    $this->assertEquals($gridstack->label(), $gridstack->label(), format_string('GridStack::load: Proper title for gridstack optionset %gridstack.', $t_args));

    // Check that the gridstack was created in site default language.
    $this->assertEquals($gridstack->language()->getId(), $default_langcode, format_string('GridStack::load: Proper language code for gridstack optionset %gridstack.', $t_args));
  }

}
