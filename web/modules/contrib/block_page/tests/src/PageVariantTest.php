<?php

/**
 * @file
 * Contains \Drupal\block_page\Tests\PageVariantTest.
 */

namespace Drupal\block_page\Tests;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the page variant plugin.
 *
 * @coversDefaultClass \Drupal\block_page\Plugin\PageVariantBase
 *
 * @group Drupal
 * @group BlockPage
 */
class PageVariantTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Block page variant',
      'description' => '',
      'group' => 'Block Group',
    );
  }

  /**
   * Sets up a page variant plugin for testing.
   *
   * @param array $configuration
   *   An array of plugin configuration.
   * @param array $definition
   *   The plugin definition array.
   *
   * @return \Drupal\block_page\Plugin\PageVariantBase|\PHPUnit_Framework_MockObject_MockObject
   *   A mocked page variant plugin.
   */
  public function setUpPageVariant($configuration = array(), $definition = array()) {
    return $this->getMockBuilder('Drupal\block_page\Plugin\PageVariantBase')
      ->setConstructorArgs(array($configuration, 'test', $definition))
      ->setMethods(array('getRegionNames', 'access', 'render', 'getBlockBag', 'getSelectionConditions'))
      ->getMock();
  }

  /**
   * Tests the label() method.
   *
   * @covers ::label
   */
  public function testLabel() {
    $page_variant = $this->setUpPageVariant(array('label' => 'foo'));
    $this->assertSame('foo', $page_variant->label());
  }

  /**
   * Tests the label() method using a default value.
   *
   * @covers ::label
   */
  public function testLabelDefault() {
    $page_variant = $this->setUpPageVariant();
    $this->assertSame('', $page_variant->label());
  }

  /**
   * Tests the getWeight() method.
   *
   * @covers ::getWeight
   */
  public function testGetWeight() {
    $page_variant = $this->setUpPageVariant(array('weight' => 5));
    $this->assertSame(5, $page_variant->getWeight());
  }

  /**
   * Tests the getWeight() method using a default value.
   *
   * @covers ::getWeight
   */
  public function testGetWeightDefault() {
    $page_variant = $this->setUpPageVariant();
    $this->assertSame(0, $page_variant->getWeight());
  }

  /**
   * Tests the getRegionName() method.
   *
   * @covers ::getRegionName
   *
   * @dataProvider providerTestGetRegionName
   */
  public function testGetRegionName($region_name, $expected) {
    $page_variant = $this->setUpPageVariant();
    $page_variant->expects($this->once())
      ->method('getRegionNames')
      ->will($this->returnValue(array(
        'test1' => 'Test 1',
        'test2' => 'Test 2',
      )));
    $this->assertSame($expected, $page_variant->getRegionName($region_name));
  }

  public function providerTestGetRegionName() {
    return array(
      array('test1', 'Test 1'),
      array('test2', 'Test 2'),
      array('test3', ''),
    );
  }

  /**
   * Tests the getRegionAssignments() method.
   *
   * @covers ::getRegionAssignments
   *
   * @dataProvider providerTestGetRegionAssignments
   */
  public function testGetRegionAssignments($expected, $blocks = array()) {
    $block_bag = $this->getMockBuilder('Drupal\block_page\Plugin\BlockPluginBag')
      ->disableOriginalConstructor()
      ->getMock();
    $block_bag->expects($this->once())
      ->method('getAllByRegion')
      ->will($this->returnValue($blocks));

    $page_variant = $this->setUpPageVariant();
    $page_variant->expects($this->once())
      ->method('getBlockBag')
      ->will($this->returnValue($block_bag));
    $page_variant->expects($this->once())
      ->method('getRegionNames')
      ->will($this->returnValue(array(
        'test1' => 'Test 1',
        'test2' => 'Test 2',
      )));

    $this->assertSame($expected, $page_variant->getRegionAssignments());
  }

  public function providerTestGetRegionAssignments() {
    return array(
      array(
        array(
          'test1' => array(),
          'test2' => array(),
        ),
      ),
      array(
        array(
          'test1' => array('foo'),
          'test2' => array(),
        ),
        array(
          'test1' => array('foo'),
        ),
      ),
      array(
        array(
          'test1' => array(),
          'test2' => array(),
        ),
        array(
          'test3' => array('foo'),
        ),
      ),
      array(
        array(
          'test1' => array(),
          'test2' => array('foo'),
        ),
        array(
          'test2' => array('foo'),
          'test3' => array('bar'),
        ),
      ),
    );
  }

  /**
   * Tests the getConfiguration() method.
   *
   * @covers ::getConfiguration
   */
  public function testGetConfiguration() {
    $block_bag = $this->getMockBuilder('Drupal\block_page\Plugin\BlockPluginBag')
      ->disableOriginalConstructor()
      ->getMock();
    $block_bag->expects($this->once())
      ->method('getConfiguration')
      ->will($this->returnValue(array()));
    $condition_bag = $this->getMockBuilder('Drupal\block_page\Plugin\ConditionPluginBag')
      ->disableOriginalConstructor()
      ->getMock();
    $condition_bag->expects($this->once())
      ->method('getConfiguration')
      ->will($this->returnValue(array()));
    $page_variant = $this->setUpPageVariant();
    $page_variant->expects($this->once())
      ->method('getBlockBag')
      ->will($this->returnValue($block_bag));
    $page_variant->expects($this->once())
      ->method('getSelectionConditions')
      ->will($this->returnValue($condition_bag));

    $expected = array(
      'id' => 'test',
      'blocks' => array(),
      'selection_conditions' => array(),
      'label' => '',
      'uuid' => '',
      'weight' => 0,
      'selection_logic' => 'and',
    );
    $this->assertSame($expected, $page_variant->getConfiguration());
  }

}
