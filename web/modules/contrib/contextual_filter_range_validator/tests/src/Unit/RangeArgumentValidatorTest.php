<?php

namespace Drupal\Tests\contextual_filter_range_validator\Unit;

use Drupal\contextual_filter_range_validator\Plugin\views\argument_validator\RangeArgumentValidator;
use Drupal\Tests\UnitTestCase;

/**
 * RangeArgumentValidator units tests.
 */
/**
 * @coversDefaultClass  \Drupal\contextual_filter_range_validator\Plugin\views\argument_validator\RangeArgumentValidator
 * @group contextual_filter_range_validator
 */
class RangeArgumentValidatorTest extends UnitTestCase {

  /**
   * The view executable.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $executable;

  /**
   * The view display.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  protected $display;

  /**
   * The tested argument validator.
   *
   * @var \Drupal\views\Plugin\views\argument_validator\Entity
   */
  protected $argumentValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $this->argumentValidator = new RangeArgumentValidator([], 'range_test', []);
  }

  /**
   * @covers ::validateArgument
   *
   * @param array $settings
   *   Range validator settings.
   *
   * @param mixed $argument
   *   Argument to test.
   *
   * @param mixed $expected
   *   Expected result.
   *
   * @dataProvider argumentDataProvider
   */
  public function testValidateArgument(array $settings, $argument, $expected) {
    $default_options = [];
    $default_options['access'] = TRUE;
    $default_options['bundles'] = [];
    $default_options['operation'] = 'test_op';
    $options = array_merge($default_options, $settings);
    $this->argumentValidator->init($this->executable, $this->display, $options);

    $this->assertEquals(
      $this->argumentValidator->validateArgument($argument),
      $expected
    );
  }

  /**
   * Data provider for testValidateArgument().
   *
   * @return array
   *   Nested arrays of values to check:
   *   - $settings
   *   - $argument
   *   - $expected
   *
   * @see RangeArgumentValidatorTest::testValidateArgument()
   */
  public function argumentDataProvider() {
    return [
      [['range_min' => 0, 'range_max' => 1], -1, FALSE],
      [['range_min' => 0, 'range_max' => 1], 0, TRUE],
      [['range_min' => 0, 'range_max' => 1], 1, TRUE],
      [['range_min' => 0, 'range_max' => 1], 2, FALSE],
      [['range_min' => 0, 'range_max' => NULL], -1, FALSE],
      [['range_min' => 0, 'range_max' => NULL], 10, TRUE],
      [['range_min' => NULL, 'range_max' => 99], -100, TRUE],
      [['range_min' => NULL, 'range_max' => 99], 100, FALSE],
      [['range_min' => NULL, 'range_max' => NULL], -100, TRUE],
      [['range_min' => NULL, 'range_max' => NULL], 0, TRUE],
      [['range_min' => NULL, 'range_max' => NULL], 100, TRUE],
      [['range_min' => 1, 'range_max' => 100], 'a', FALSE],
    ];
  }

}
