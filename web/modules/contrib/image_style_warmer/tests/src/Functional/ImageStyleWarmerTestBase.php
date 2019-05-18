<?php

namespace Drupal\Tests\image_style_warmer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Image Style Warmer test base class.
 *
 * @group image_style_warmer
 */
abstract class ImageStyleWarmerTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'image', 'image_style_warmer'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Test initial image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $testInitialStyle;

  /**
   * Test queue image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $testQueueStyle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);

    // Create test image style for initial tests.
    $this->testInitialStyle = $this->container->get('entity_type.manager')->getStorage('image_style')->create([
      'name' => 'test_initial',
      'label' => 'Test initial image style',
      'effects' => [],
    ]);
    $this->testInitialStyle->save();

    // Create test image style for queue tests.
    $this->testQueueStyle = $this->container->get('entity_type.manager')->getStorage('image_style')->create([
      'name' => 'test_queue',
      'label' => 'Test queue image style',
      'effects' => [],
    ]);
    $this->testQueueStyle->save();
  }

}
