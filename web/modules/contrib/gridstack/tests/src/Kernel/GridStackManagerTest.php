<?php

namespace Drupal\Tests\gridstack\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Messenger\Messenger;
use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\gridstack\Traits\GridStackUnitTestTrait;
use Drupal\gridstack\GridStackDefault;
use Drupal\gridstack\Entity\GridStack;
use Drupal\gridstack_ui\Form\GridStackForm;

/**
 * Tests the GridStack manager methods.
 *
 * @coversDefaultClass \Drupal\gridstack\GridStackManager
 *
 * @group gridstack
 */
class GridStackManagerTest extends BlazyKernelTestBase {

  use GridStackUnitTestTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'file',
    'filter',
    'image',
    'media',
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

    $this->installConfig([
      'field',
      'node',
      'views',
      'blazy',
      'gridstack',
    ]);

    $bundle = $this->bundle;

    $this->fileSystem          = $this->container->get('file_system');
    $this->messenger           = $this->container->get('messenger');
    $this->gridstackAdmin      = $this->container->get('gridstack.admin');
    $this->blazyAdminFormatter = $this->gridstackAdmin;
    $this->gridstackFormatter  = $this->container->get('gridstack.formatter');
    $this->gridstackManager    = $this->container->get('gridstack.manager');

    $this->gridstackForm = new GridStackForm(
      $this->fileSystem,
      $this->messenger,
      $this->blazyAdmin,
      $this->gridstackManager
    );

    $this->testPluginId  = 'gridstack_image';
    $this->testFieldName = 'field_gridstack_image';
    $this->maxItems      = 7;
    $this->maxParagraphs = 2;

    $settings['fields']['field_text_multiple'] = 'text';
    $this->setUpContentTypeTest($bundle, $settings);
    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();

    $this->display = $this->setUpFormatterDisplay($bundle);
    $this->formatterInstance = $this->getFormatterInstance();

    // Enable Boostrap support.
    $this->blazyManager->getConfigFactory()->getEditable('gridstack.settings')->set('framework', 'bootstrap')->save();
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::attach
   * @covers ::getSkins
   * @covers ::getSkinOptions
   */
  public function testGridStackManagerMethods() {
    $manager = $this->gridstackManager;

    $settings = [
      'use_js'      => TRUE,
      'skin'        => 'selena',
      'width'       => 11,
      'breakpoints' => ['lg' => ['column' => 11]],
    ] + $this->getFormatterSettings();

    $attachments = $manager->attach($settings);
    $this->assertArrayHasKey('gridstack', $attachments['drupalSettings']);

    // Tests for skins.
    $skins = $manager->getSkins();
    $this->assertArrayHasKey('default', $skins);

    // Verify we have cached skins.
    $cid = 'gridstack:skins';
    $cached_skins = $manager->getCache()->get($cid);
    $this->assertEquals($cid, $cached_skins->cid);
    $this->assertEquals($skins, $cached_skins->data);

    // Verify skins has default skin.
    $defined_skins = $manager->getSkinOptions();
    $this->assertArrayHasKey('default', $defined_skins);
  }

  /**
   * Tests for GridStack build.
   *
   * @param bool $items
   *   Whether to provide items, or not.
   * @param array $settings
   *   The settings being tested.
   * @param mixed|bool|string $expected
   *   The expected output.
   *
   * @covers ::build
   * @covers ::preRenderGridStack
   * @dataProvider providerTestGridStackBuild
   */
  public function testBuild($items, array $settings, $expected) {
    $manager = $this->gridstackManager;
    $defaults = $this->getFormatterSettings() + GridStackDefault::htmlSettings();
    $settings = array_merge($defaults, $settings) + GridStackDefault::imageSettings();

    $settings['optionset'] = 'test';

    $build = $this->display->build($this->entity);

    $items = !$items ? [] : $build[$this->testFieldName]['#build']['items'];
    $build = [
      'items'     => $items,
      'settings'  => $settings,
      'optionset' => GridStack::loadWithFallback($settings['optionset']),
    ];

    $gridstack = $manager->build($build);
    $this->assertEquals($expected, !empty($gridstack));
  }

  /**
   * Provide test cases for ::testBuild().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestGridStackBuild() {
    $data[] = [
      FALSE,
      [],
      FALSE,
    ];
    $data[] = [
      TRUE,
      [
        'skin' => 'selena',
      ],
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests for \Drupal\gridstack_ui\Form\GridStackForm.
   *
   * @covers \Drupal\gridstack_ui\Form\GridStackForm::getColumnOptions
   * @covers \Drupal\gridstack_ui\Form\GridStackForm::jsonify
   */
  public function testGridStackForm() {
    $frontend = GridStack::load('frontend');

    $options = $this->gridstackForm->getColumnOptions();
    $this->assertArrayHasKey(2, $options);

    $json = $this->gridstackForm->jsonify();
    $this->assertEmpty($json);

    $settings = $frontend->getSettings();
    $settings['cellHeight'] = '70';
    $settings['rtl'] = '1';
    $json = $this->gridstackForm->jsonify($settings);
    $this->assertTrue(is_string($json));

    $array = Json::decode($json);
    $this->assertEquals(70, $array['cellHeight']);
    $this->assertEquals(TRUE, $array['rtl']);
  }

}
