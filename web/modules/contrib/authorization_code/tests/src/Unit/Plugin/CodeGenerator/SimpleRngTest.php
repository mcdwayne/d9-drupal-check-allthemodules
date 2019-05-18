<?php

namespace Drupal\Tests\authorization_code\Unit\Plugin\CodeGenerator;

use Drupal\authorization_code\CodeGeneratorInterface;
use Drupal\authorization_code\Plugin\CodeGenerator\SimpleRng;
use Drupal\Tests\UnitTestCase;

/**
 * Simple RNG unit tests.
 *
 * @group authorization_code
 */
class SimpleRngTest extends UnitTestCase {

  /**
   * Tests the generate function of SimpleRNG.
   *
   * @param \Drupal\authorization_code\CodeGeneratorInterface $generator
   *   The generator plugin.
   * @param int $expected_code_size
   *   The expected code size.
   *
   * @dataProvider providerTestGenerate
   */
  public function testGenerate(CodeGeneratorInterface $generator, int $expected_code_size) {
    $this->assertInternalType('string', $generator->generate());
    $this->assertSame($expected_code_size, strlen($generator->generate()));
    $this->assertRegExp('/\d+/', $generator->generate(), 'Code is comprised of digits');
  }

  /**
   * Provides testing data for testGenerate.
   *
   * @return array
   *   Each item in the array is a tuple of a plugin instance and the expected
   *   result of the test.
   */
  public function providerTestGenerate(): array {
    return [
      [$this->createPlugin(), CodeGeneratorInterface::DEFAULT_CODE_LENGTH],
      [$this->createPlugin(6), 6],
      [$this->createPlugin(15), 15],
    ];
  }

  /**
   * Create a simple RNG plugin instance.
   *
   * @param int|false $code_length
   *   The code length, or false for the default value.
   *
   * @return \Drupal\authorization_code\Plugin\CodeGenerator\SimpleRng
   *   The plugin instance.
   */
  private function createPlugin($code_length = FALSE): SimpleRng {
    return new SimpleRng([
      'plugin_id' => 'simple_rng',
      'settings' => $code_length ? ['code_length' => $code_length] : [],
    ], 'simple_rng', []);
  }

}
