<?php

namespace Drupal\Tests\onlyone_admin_toolbar\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\onlyone_admin_toolbar\OnlyOneAdminToolbar;

/**
 * Tests the Language class methods.
 *
 * @group onlyone
 * @group onlyone_admin_toolbar
 * @coversDefaultClass \Drupal\onlyone_admin_toolbar\OnlyOneAdminToolbar
 */
class OnlyOneAdminToolbarTest extends UnitTestCase {

  /**
   * A config factory instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * A route builder instance.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeBuilder;

  /**
   * The OnlyOneAdminToolbar Object.
   *
   * @var Drupal\onlyone_admin_toolbar\OnlyOneAdminToolbar
   */
  protected $onlyOneAdminToolbar;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Config factory mock.
    $this->configFactory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    // Route builder mock.
    $this->routeBuilder = $this->createMock('Drupal\Core\Routing\RouteBuilderInterface');

    // Creating the object.
    $this->onlyOneAdminToolbar = new OnlyOneAdminToolbar($this->configFactory, $this->routeBuilder);
  }

  /**
   * Tests the OnlyOneAdminToolbar::rebuildMenu() method.
   *
   * @param string $content_type
   *   Content type machine name.
   * @param array $content_types_list
   *   Array of content types machine names.
   *
   * @covers ::rebuildMenu
   * @dataProvider providerRebuildMenu
   */
  public function testRebuildMenu($content_type, array $content_types_list) {
    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('onlyone_node_types')
      ->willReturn($content_types_list);

    // Mocking get method.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('onlyone.settings')
      ->willReturn($config);

    // Mocking rebuild method.
    $this->routeBuilder->expects($this->any())
      ->method('rebuild')
      ->willReturn(TRUE);

    // Testing the function.
    $this->assertNull($this->onlyOneAdminToolbar->rebuildMenu($content_type));
  }

  /**
   * Data provider for testRebuildMenu().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'content_type' - Content type machine name.
   *   - 'content_types_list' - Array of content types machine names.
   *
   * @see testRebuildMenu()
   */
  public function providerRebuildMenu() {
    $content_types_list = ['page', 'forum', 'article'];

    $tests['configured content type'] = ['page', $content_types_list];
    $tests['configured content type'] = ['article', $content_types_list];
    $tests['not configured content type'] = ['blog', $content_types_list];
    $tests['not configured content type'] = ['log', $content_types_list];

    return $tests;
  }

}
