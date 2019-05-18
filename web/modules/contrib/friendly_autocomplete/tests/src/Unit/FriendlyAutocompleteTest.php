<?php

namespace Drupal\Tests\friendly_autocomplete\Unit;

use Drupal\friendly_autocomplete\Element\FriendlyAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Autocomplete FormElement class.
 *
 * @covers \Drupal\friendly_autocomplete\Element\FriendlyAutocomplete
 *
 * @group Render
 */
class FriendlyAutocompleteTest extends UnitTestCase {

  /**
   * @covers \Drupal\friendly_autocomplete\Element\FriendlyAutocomplete::valueCallback
   *
   * @dataProvider providerTestValueCallback
   */
  public function testValueCallback($input, $expected, $element) {
    /** @var \Drupal\Core\Form\FormStateInterface $form_state */
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $this->assertSame($expected, FriendlyAutocomplete::valueCallback($element, $input, $form_state));
  }

  /**
   * Data provider for testValueCallback().
   */
  public function providerTestValueCallback() {
    $data = [];

    $default_element = ['#default_value' => ''];

    // Test that given no data, no data comes back out.
    $data[] = [
      ['autocomplete_label' => '', 'autocomplete_value' => ''],
      ['autocomplete_label' => '', 'autocomplete_value' => ''],
      $default_element,
    ];
    // Test that given data, the same data comes back out.
    $data[] = [
      ['autocomplete_label' => 'test', 'autocomplete_value' => '123'],
      ['autocomplete_label' => 'test', 'autocomplete_value' => '123'],
      $default_element,
    ];
    // Test without ANYTHING, default values should come back out.
    $data[] = [
      FALSE,
      ['autocomplete_label' => '', 'autocomplete_value' => ''],
      $default_element,
    ];
    // Test that extraneous elements are removed.
    $data[] = [
      [
        'autocomplete_label' => 'test',
        'autocomplete_value' => '123',
        'qwerty' => 'wsxfdl',
      ],
      ['autocomplete_label' => 'test', 'autocomplete_value' => '123'],
      $default_element,
    ];

    return $data;
  }

}
