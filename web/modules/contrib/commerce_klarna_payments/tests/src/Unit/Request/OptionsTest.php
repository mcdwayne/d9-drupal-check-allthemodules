<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Request\Payment\Options;
use Drupal\Tests\UnitTestCase;

/**
 * Options request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\Payment\Options
 */
class OptionsTest extends UnitTestCase {

  /**
   * @covers ::assertColor
   * @covers ::setColor
   * @covers ::setButtonColor
   * @covers ::setButtonTextColor
   * @covers ::setCheckBoxColor
   * @covers ::setCheckBoxCheckMarkColor
   * @covers ::setHeaderColor
   * @covers ::setLinkColor
   * @covers ::setBorderColor
   * @covers ::setSelectedBorderColor
   * @covers ::setTextColor
   * @covers ::setDetailsColor
   * @covers ::setSecondaryTextColor
   * @covers ::setBorderRadius
   * @dataProvider optionsDataProvider
   */
  public function testOptions(array $data) {
    $options = Options::create($data);
    $this->assertEquals($data, $options->toArray());
  }

  /**
   * @covers ::setColor
   * @covers ::assertColor
   * @expectedException \InvalidArgumentException
   * @dataProvider exceptionDataProvider
   */
  public function testException(string $color) {
    $options = new Options();
    $options->setButtonTextColor($color);
  }

  /**
   * Data provider for testException().
   *
   * @return array
   *   The data.
   */
  public function exceptionDataProvider() {
    return [
      [
        '#FFF',
      ],
      [
        '#123',
      ],
      [
        '#12345az',
      ],
    ];
  }

  /**
   * Data provider for testOptions().
   *
   * @return array
   *   The data.
   */
  public function optionsDataProvider() {
    return [
      [
        [
          'color_button' => '#111111',
        ],
        [
          'color_button' => '#121212',
          'color_button_text' => '131313',
          'color_checkbox' => '141414',
          'color_checkbox_checkmark' => '#141414',
          'color_header' => '#151515',
          'color_link' => '#161616',
          'color_border' => '#171717',
          'color_border_selected' => '#181818',
          'color_text' => '#191919',
          'color_details' => '#202020',
          'color_text_secondary' => '#212121',
          'radius_border' => '5',
        ],
      ],
    ];
  }

}
