<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\DefaultValue;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the default value plugin.
 *
 * @group tamper
 */
class DefaultValueTest extends UnitTestCase {

  /**
   * Test anything changed to the value, even if value existed before.
   */
  public function testAnythingToDefaultValue() {
    $config = [
      DefaultValue::SETTING_DEFAULT_VALUE => 'HEYO!',
      DefaultValue::SETTING_ONLY_IF_EMPTY => FALSE,
    ];
    $plugin = new DefaultValue($config, 'default_value', []);
    $this->assertEquals('HEYO!', $plugin->tamper('asdfasdf'));
    $this->assertEquals('HEYO!', $plugin->tamper(['asdfasdf']));
    $this->assertEquals('HEYO!', $plugin->tamper([]));
  }

  /**
   * Test only empty value changed to the default value.
   */
  public function testOnlyIfEmptyToDefaultValue() {
    $config = [
      DefaultValue::SETTING_DEFAULT_VALUE => 'HEYO!',
      DefaultValue::SETTING_ONLY_IF_EMPTY => TRUE,
    ];
    $plugin = new DefaultValue($config, 'default_value', []);
    $this->assertEquals('HEYO!', $plugin->tamper([]));
    $this->assertEquals([1], $plugin->tamper([1]));
  }

}
