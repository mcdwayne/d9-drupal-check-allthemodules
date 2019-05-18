<?php

namespace Drupal\config_override\Tests\Unit;

use Drupal\config_override\EnvironmentConfigOverride;

/**
 * @coversDefaultClass \Drupal\config_override\EnvironmentConfigOverride
 * @group config_override
 */
class EnvironmentConfigOverrideTest extends \PHPUnit_Framework_TestCase {

  public function testEmptyOverride() {
    $sut = new EnvironmentConfigOverride([]);

    $this->assertEquals([], $sut->loadOverrides([]));
  }

  public function testStaticOverridesOtherNames() {
    $sut = new EnvironmentConfigOverride([
      'example' => [
        'key' => 'value',
      ],
    ]);
    $this->assertEquals([], $sut->loadOverrides(['other_example']));
  }

  public function testStaticOverridesOverriddenNames() {
    $sut = new EnvironmentConfigOverride([
      'example' => [
        'key' => 'value',
      ],
    ]);
    $this->assertEquals(['example' => ['key' => 'value']],
      $sut->loadOverrides(['example']));
  }

  public function testStaticOverridesWithOverriddenNamesAndOtherNames() {
    $sut = new EnvironmentConfigOverride([
      'example' => [
        'key' => 'value',
      ],
    ]);
    $this->assertEquals(['example' => ['key' => 'value']],
      $sut->loadOverrides(['example', 'other_example']));
  }

  public function testStaticOverridesOverriddenNamesWithDynamicEnvironment() {
    putenv('CONFIG___EXAMPLE___KEY=value_overridden');
    putenv('CONFIG___EXAMPLE_3___KEY3=value3_overridden');
    putenv('CONFIG___EXAMPLE__4___KEY4=value4_overridden');
    $sut = new EnvironmentConfigOverride([
      'example' => [
        'key' => 'value',
        'key2' => 'value2',
      ],
      'example_3' => [
        'key3' => 'value3',
      ],
      'example.4' => [
      'key4' => 'value4',
    ]
    ]);
    $this->assertEquals([
      'example' => ['key' => 'value_overridden', 'key2' => 'value2'],
      'example_3' => [
        'key3' => 'value3_overridden',
      ],
      'example.4' => [
      'key4' => 'value4_overridden',
    ]
    ],
      $sut->loadOverrides(['example', 'example_3', 'example.4']));
  }

  public function testStaticOverridesWithArray() {
    $sut = new EnvironmentConfigOverride([
      'example' => [
        'key' => '{"hey":"giraffe"}',
      ],
    ]);
    $this->assertEquals(['example' => ['key' => ['hey' => 'giraffe']]], $sut->loadOverrides(['example']));
  }

  public function testStaticOverridesWithArrayEnvironment() {
    putenv('CONFIG___EXAMPLE___KEY={"hey":"elephant"}');
    $sut = new EnvironmentConfigOverride([
      'example' => [
        'key' => '{"hey":"giraffe"}',
      ],
    ]);
    $this->assertEquals(['example' => ['key' => ['hey' => 'elephant']]], $sut->loadOverrides(['example']));
  }

}
