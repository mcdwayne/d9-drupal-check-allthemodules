<?php

namespace Drupal\Tests\gridstack\Kernel;

use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\gridstack\Traits\GridStackUnitTestTrait;

/**
 * Tests the GridStack field rendering using the image field type.
 *
 * @coversDefaultClass \Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackImageFormatter
 * @group gridstack
 */
class GridStackFormatterTest extends BlazyKernelTestBase {

  use GridStackUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
    'filter',
    'node',
    'text',
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

    $this->testFieldName      = 'field_image_multiple';
    $this->testEmptyName      = 'field_image_multiple_empty';
    $this->testPluginId       = 'gridstack_image';
    $this->maxItems           = 7;
    $this->maxParagraphs      = 2;
    $this->gridstackAdmin     = $this->container->get('gridstack.admin');
    $this->gridstackManager   = $this->container->get('gridstack.manager');
    $this->gridstackFormatter = $this->container->get('gridstack.formatter');

    $data['fields'] = [
      'field_video'                => 'text',
      'field_image'                => 'image',
      'field_image_multiple_empty' => 'image',
    ];

    // Create contents.
    $bundle = $this->bundle;
    $this->setUpContentTypeTest($bundle, $data);

    $settings = [
      'optionset' => 'frontend',
    ] + $this->getFormatterSettings();

    $data['settings'] = $settings;
    $this->display = $this->setUpFormatterDisplay($bundle, $data);

    $data['plugin_id'] = $this->testPluginId;
    $this->displayEmpty = $this->setUpFormatterDisplay($bundle, $data);

    $this->formatterInstance = $this->getFormatterInstance();
    $this->skins = $this->gridstackManager->getSkins();

    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();

    // Enable Boostrap support.
    $this->blazyManager->getConfigFactory()->getEditable('gridstack.settings')->set('framework', 'bootstrap')->save();
  }

  /**
   * Tests the GridStack formatters.
   */
  public function testGridStackFormatter() {
    $entity = $this->entity;

    // Generate the render array to verify if the cache tags are as expected.
    $build = $this->display->build($entity);
    $build_empty = $this->displayEmpty->build($entity);

    $render = $this->gridstackManager->getRenderer()->renderRoot($build);
    $this->assertNotEmpty($render);

    $render_empty = $this->gridstackManager->getRenderer()->renderRoot($build_empty[$this->testEmptyName]);
    $this->assertEmpty($render_empty);

    $this->assertInstanceOf('\Drupal\Core\Field\FieldItemListInterface', $this->testItems);
    $this->assertInstanceOf('\Drupal\gridstack\Form\GridStackAdminInterface', $this->formatterInstance->admin());
    $this->assertInstanceOf('\Drupal\gridstack\GridStackFormatterInterface', $this->formatterInstance->formatter());
    $this->assertInstanceOf('\Drupal\gridstack\GridStackManagerInterface', $this->formatterInstance->manager());

    $component = $this->display->getComponent($this->testFieldName);
    $this->assertEquals($this->testPluginId, $component['type']);

    $scopes = $this->formatterInstance->getScopedFormElements();
    $this->assertEquals(FALSE, $scopes['breakpoints']);
    $this->assertArrayHasKey('optionset', $scopes['settings']);

    $summary = $this->formatterInstance->settingsSummary();
    $this->assertNotEmpty($summary);
  }

  /**
   * Tests for \Drupal\gridstack\GridStackFormatter.
   *
   * @param array $settings
   *   The settings being tested.
   * @param mixed|bool|string $expected
   *   The expected output.
   *
   * @covers \Drupal\gridstack\GridStackFormatter::buildSettings
   * @dataProvider providerTestBuildSettings
   */
  public function testBuildSettings(array $settings, $expected) {
    $format['settings'] = array_merge($this->getFormatterSettings(), $settings);

    $this->gridstackFormatter->buildSettings($format, $this->testItems);
    $this->assertArrayHasKey('bundle', $format['settings']);
  }

  /**
   * Provide test cases for ::testBuildSettings().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestBuildSettings() {
    $breakpoints = $this->getDataBreakpoints(TRUE);

    $data[] = [
      [
        'breakpoints' => [],
      ],
      FALSE,
    ];
    $data[] = [
      [
        'breakpoints' => [],
        'blazy'       => FALSE,
      ],
      TRUE,
    ];
    $data[] = [
      [
        'breakpoints' => $breakpoints,
        'blazy'       => TRUE,
      ],
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests for \Drupal\gridstack\Form\GridStackAdmin.
   *
   * @covers \Drupal\gridstack\Form\GridStackAdmin::buildSettingsForm
   * @covers \Drupal\gridstack\Form\GridStackAdmin::openingForm
   * @covers \Drupal\gridstack\Form\GridStackAdmin::mainForm
   * @covers \Drupal\gridstack\Form\GridStackAdmin::closingForm
   * @covers \Drupal\gridstack\Form\GridStackAdmin::finalizeForm
   * @covers \Drupal\gridstack\Form\GridStackAdmin::getLayoutOptions
   * @covers \Drupal\gridstack\Form\GridStackAdmin::getSkinOptions
   * @covers \Drupal\gridstack\Form\GridStackAdmin::getSettingsSummary
   * @covers \Drupal\gridstack\Form\GridStackAdmin::getFieldOptions
   */
  public function testAdminOptions() {
    $definition = $this->getGridStackFormatterDefinition();
    $form['test'] = ['#type' => 'hidden'];

    $this->gridstackAdmin->buildSettingsForm($form, $definition);
    $this->assertArrayHasKey('optionset', $form);

    $this->gridstackAdmin->finalizeForm($form, $definition);
    $this->assertArrayHasKey('closing', $form);

    $options = $this->gridstackAdmin->getLayoutOptions();
    $this->assertArrayHasKey('bottom', $options);

    $options = $this->gridstackAdmin->getSkinOptions();
    $this->assertArrayHasKey('selena', $options);

    $summary = $this->gridstackAdmin->getSettingsSummary($definition);
    $this->assertNotEmpty($summary);

    $options = $this->gridstackAdmin->getFieldOptions([], [], 'node');
    $this->assertArrayHasKey($this->testFieldName, $options);
  }

}
