<?php

namespace Drupal\Tests\dream_fields\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Test the plugin manager.
 *
 * @group dream_fields
 */
class PluginManagerTest extends UnitTestCase {

  /**
   * Test the plugin manager weight.
   */
  public function testPluginManagerWeightOrdering() {
    $manager = $this->getMockManager(['foo' => [
      'id' => 'foo',
      'weight' => 10,
      'field_types' => ['text'],
    ],
    'bar' => [
      'id' => 'bar',
      'weight' => -10,
      'field_types' => ['text'],
    ]], ['text']);
    $sorted_definitions = $manager->getDefinitions();
    $first = array_shift($sorted_definitions);
    $second = array_shift($sorted_definitions);
    $this->assertEquals('bar', $first['id']);
    $this->assertEquals('foo', $second['id']);
  }

  /**
   * Test the field dependencies are detected.
   */
  public function testFieldDependencies() {
    $manager = $this->getMockManager(['foo' => [
      'id' => 'foo',
      'weight' => 0,
      'field_types' => ['rare_field'],
    ],
    'bar' => [
      'id' => 'bar',
      'weight' => 0,
      'field_types' => ['text'],
    ]], ['text']);
    $definitions = $manager->getDefinitions();
    $this->assertTrue(empty($definitions['foo']));
    $this->assertNotEmpty($definitions['bar']);
  }

  /**
   * Get the mock manager.
   *
   * @param array $definitions
   *   An array of definitions for the mock to use.
   * @param array $enabled_fields
   *   An array of fields the manager will consider "enabled".
   *
   * @return \Drupal\dream_fields\DreamFieldsPluginManager
   *   A mock plugin manager.
   */
  protected function getMockManager($definitions, $enabled_fields) {
    $discovery = $this->getMock('\Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $discovery
      ->expects($this->any())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $manager = $this->getMockBuilder('\Drupal\dream_fields\DreamFieldsPluginManager')
      ->setMethods(['getDiscovery', 'getEnabledFields'])
      ->disableOriginalConstructor()
      ->getMock();
    $manager
      ->expects($this->any())
      ->method('getDiscovery')
      ->willReturn($discovery);
    $manager
      ->expects($this->any())
      ->method('getEnabledFields')
      ->willReturn($enabled_fields);
    return $manager;
  }

}
