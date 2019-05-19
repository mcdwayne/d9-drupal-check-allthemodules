<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Layout test.
 *
 * @group snippet_manager
 */
class LayoutTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['layout_discovery'];

  /**
   * Test callback.
   */
  public function testSnippetLayout() {

    // -- Check layout form appearance.
    $this->drupalGet($this->snippetEditUrl);
    $prefix = '//fieldset[legend/span[text()="Layout"]]';
    $this->assertXpath($prefix . '//input[@name="layout[status]" and not(@checked)]/next::label[text()="Enable snippet layout"]');
    $this->assertXpath($prefix . '//label[text()="Label"]/next::input[@name="layout[label]"]');

    // -- Enable layout.
    $edit = [
      'layout[status]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertXpath($prefix . '//input[@name="layout[status]" and @checked]');

    $edit = [
      'template[value]' => '{{ sidebar }}{{ main }}',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/template', $edit, 'Save');

    // -- Create sidebar variable.
    $edit = [
      'plugin_id' => 'layout_region',
      'name' => 'sidebar',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/variable/add', $edit, 'Save and continue');
    $this->assertXpath('//label[text()="Label"]/next::input[@name="configuration[label]" and @value="Sidebar"]');
    $this->assertXpath('//label[text()="Weight"]/next::input[@name="configuration[weight]" and @value="0"]');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertStatusMessage('The variable has been updated.');

    // -- Create main variable.
    $edit = [
      'plugin_id' => 'layout_region',
      'name' => 'main',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/variable/add', $edit, 'Save and continue');
    $this->assertXpath('//label[text()="Label"]/next::input[@name="configuration[label]" and @value="Main"]');
    $this->assertXpath('//label[text()="Weight"]/next::input[@name="configuration[weight]" and @value="0"]');
    $edit = [
      'configuration[label]' => 'Main content',
      'configuration[weight]' => 10,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertStatusMessage('The variable has been updated.');

    // -- Set default region.
    $this->drupalGet($this->snippetEditUrl);

    // Check default region form.
    $default_region_xpath = $prefix . '//fieldset[legend/span[text()="Default region"]]/div';
    $default_region_xpath .= '//div[@class="form-radios"]';
    $sidebar_xpath = 'input[@name="layout[default_region]" and @value="sidebar"]';
    $sidebar_xpath .= '/next::label[text()="Sidebar"]';
    $default_region_xpath .= "/div[$sidebar_xpath]";
    $main_xpath = 'input[@name="layout[default_region]" and @value="main"]';
    $main_xpath .= '/next::label[text()="Main content"]';
    $default_region_xpath .= "/next::div[$main_xpath]";
    $this->assertXpath($default_region_xpath);

    $edit = [
      'layout[default_region]' => 'main',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertXpath('//input[@name="layout[default_region]" and @value="main" and @checked]');

    // -- Check layout definition.
    /** @var \Drupal\Core\Layout\LayoutPluginManager $layout_manager */
    $layout_manager = \Drupal::service('plugin.manager.core.layout');

    /** @var \Drupal\Core\Layout\LayoutDefinition $layout_definition */
    $layout_definition = $layout_manager->getDefinition('snippet_layout:' . $this->snippetId);
    $this->assertEquals($this->snippetLabel, $layout_definition->getLabel());
    $this->assertEquals('Snippets', $layout_definition->getCategory());
    $expected_regions = [
      'sidebar' => [
        'label' => 'Sidebar',
        'weight' => 0,
      ],
      'main' => [
        'label' => 'Main content',
        'weight' => 10,
      ],
    ];
    $this->assertEquals($expected_regions, $layout_definition->getRegions());
    $this->assertEquals('main', $layout_definition->getDefaultRegion());

    // -- Check layout rendering.
    $layout_plugin = $layout_manager->createInstance('snippet_layout:' . $this->snippetId);
    $regions['main']['#markup'] = 'Main content here.';
    $regions['sidebar']['#markup'] = 'Sidebar here.';
    $build = $layout_plugin->build($regions);

    $expected_output = implode([
      '<div class="snippet-layout layout layout--%s">',
      '<div class="layout__region layout__region--sidebar">Sidebar here.</div>',
      '<div class="layout__region layout__region--main">Main content here.</div>',
      '</div>',
    ]);
    $actual_output = \Drupal::service('renderer')->renderPlain($build);

    $this->assertEquals(
      sprintf($expected_output, $this->snippetId),
      trim(preg_replace('#>\s+<#', '><', $actual_output))
    );
  }

}
