<?php

namespace Drupal\Test\jquery_colorpicker\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\jquery_colorpicker\Element\JQueryColorpickerElement;

/**
 * @coversDefaultClass \Drupal\jquery_colorpicker\Element\JQueryColorpickerElement
 * @group jquery_colorpicker
 */
class JQueryColorpickerTest extends UnitTestCase {

  /**
   * @covers ::valueCallback
   *
   * @dataProvider providerTestValueCallback
   */
  public function testValueCallback($expected, $input) {
    $element = [];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $this->assertSame($expected, JQueryColorpickerElement::valueCallback($element, $input, $form_state));
  }

  /**
   * Data provider for testValueCallback()
   */
  public function providerTestValueCallback() {
    $data = [];

    $data[] = [NULL, FALSE];
    $data[] = [NULL, ['test']];
    $test = new \stdClass();
    $test->color = 'test';
    $data[] = [NULL, $test];
    $test->color = '123456';
    $data[] = [NULL, $test];
    $data[] = ['#123456', '#123456'];
    $data[] = [NULL, '1'];

    return $data;
  }

  /**
   * @covers ::valueCallback
   */
  public function testValidateElementEmpty() {
    $element = ['#value' => ''];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $this->assertSame(NULL, JQueryColorpickerElement::validateElement($element, $form_state));
  }

}
