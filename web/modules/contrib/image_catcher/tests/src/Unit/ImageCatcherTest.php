<?php

namespace Drupal\Tests\image_catcher\Unit;

use Drupal\Core\Utility\Token;
use Drupal\image_catcher\ImageCatcher;
use Drupal\pathauto\AliasCleaner;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\image_catcher\ImageCatcher
 * @group image_catcher
 */
class ImageCatcherTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->tokenManagerProphecy = $this->prophesize(Token::CLASS);
    $this->aliascleanerProphecy = $this->prophesize(AliasCleaner::CLASS);
    $this->imageCatcher = new ImageCatcher($this->tokenManagerProphecy->reveal(), $this->aliascleanerProphecy->reveal());
  }

  /**
   * Test createFromBase64 method with no mime type.
   *
   * @covers ::createFromBase64
   * @dataProvider createFromBase64NoMimeDataProvider
   */
  public function testCreateFromBase64NoMime(string $image_base64, string $dir_name, string $image_name) {
    $this->createFromBase64ReturnsFalse($image_base64, $dir_name, $image_name);
  }

  /**
   * Test createFromBase64 method with wrong mime type.
   *
   * @covers ::createFromBase64
   * @dataProvider createFromBase64WrongMimeDataProvider
   */
  public function testCreateFromBase64WrongMime(string $image_base64, string $dir_name, string $image_name) {
    $this->createFromBase64ReturnsFalse($image_base64, $dir_name, $image_name);
  }

  /**
   * Expect a FALSE return on ::createFromBase64 calls.
   */
  public function createFromBase64ReturnsFalse(string $image_base64, string $dir_name, string $image_name) {
    $this->aliascleanerProphecy->cleanString($image_name)->willReturn($image_name);
    $this->assertFalse($this->imageCatcher->createFromBase64($image_base64, $dir_name, $image_name));
  }

  /**
   * Data provider for testCreateFromBase64NoMime().
   *
   * @return array
   *   Data.
   */
  public function createFromBase64NoMimeDataProvider() {
    return [
      ["xyz", "image_catcher", "drupal-8.png"],
      [
        "iVBORw0KGgoAAAANSUhEUgAAASgAAAFPCAYAAAD+9lP7AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAG0BJREFUeNrsnUt220iyQGEcz6W3ArFmr0dirUCoFYg98dTUCiyvwNQKLK/A1LB7UtQKTK2gqFENi1rBs1agh7CCLpTED0ACyIjMe8/hkU+3yyLycxGR3zdPT08ZgPCv/z4U5Y/Fn+9OvlMaYIGcIoAKx+VnSjEAggKLSOR0XkZS1xQFICiwyodSUmOKARAUWGJR+fNXHZMCCMYbBsmhSimlaoN4LD/Fn+9OFpQMEEGBNY7Kz7yU1jFFAQgKkBQAgoKGnJafGcUACApCc7/hfz8ro6gpxQMICkKybRX5eyQFCAosI5K6pBgAQYFVPrOQExAUWOYrkgIEBdYlNaIYAEGBVaalpIYUAyAo6IOzhn9/tZATSQGCApMgKUBQgKQAQQEgKUBQYIuWxIKkAEFBJ7R1YgGSAgQFpHuAoCAdCiQFCAqIpAAQFASOoJAUIChojS6P9UVSgKDgIE5J9wBBgTl6lAaSAgQFjelTGEgKEBQ0ouj5960kxXlSgKDAVARVldTvnMwJm+Dqc8j0Us7/C/w1Lv58dzKlNoAICkKnd+vgjHNAULAWK+NAIqkJ1QEICqxFUCs+cTkorGAMKnF0qv8Pg1/",
        "image_catcher",
        "drupal-8.png",
      ],
    ];
  }

  /**
   * Data provider for testCreateFromBase64WrongMime().
   *
   * @return array
   *   Data.
   */
  public function createFromBase64WrongMimeDataProvider() {
    return [
      [
        "data:video/mpeg;base64,iVBORw0KGgoAAAANSUhEUgAAASgAAAFPCAYAAAD+9lP7AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAG0BJREFUeNrsnUt220iyQGEcz6W3ArFmr0dirUCoFYg98dTUCiyvwNQKLK/A1LB7UtQKTK2gqFENi1rBs1agh7CCLpTED0ACyIjMe8/hkU+3yyLycxGR3zdPT08ZgPCv/z4U5Y/Fn+9OvlMaYIGcIoAKx+VnSjEAggKLSOR0XkZS1xQFICiwyodSUmOKARAUWGJR+fNXHZMCCMYbBsmhSimlaoN4LD/Fn+9OFpQMEEGBNY7Kz7yU1jFFAQgKkBQAgoKGnJafGcUACApCc7/hfz8ro6gpxQMICkKybRX5eyQFCAosI5K6pBgAQYFVPrOQExAUWOYrkgIEBdYlNaIYAEGBVaalpIYUAyAo6IOzhn9/tZATSQGCApMgKUBQgKQAQQEgKUBQYIuWxIKkAEFBJ7R1YgGSAgQFpHuAoCAdCiQFCAqIpAAQFASOoJAUIChojS6P9UVSgKDgIE5J9wBBgTl6lAaSAgQFjelTGEgKEBQ0ouj5960kxXlSgKDAVARVldTvnMwJm+Dqc8j0Us7/C/w1Lv58dzKlNoAICkKnd+vgjHNAULAWK+NAIqkJ1QEICqxFUCs+cTkorGAMKnF0qv8Pg1/",
        "image_catcher",
        "drupal-8.png",
      ],
      [
        "data:image/xyz;base64,iVBORw0KGgoAAAANSUhEUgAAASgAAAFPCAYAAAD+9lP7AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAG0BJREFUeNrsnUt220iyQGEcz6W3ArFmr0dirUCoFYg98dTUCiyvwNQKLK/A1LB7UtQKTK2gqFENi1rBs1agh7CCLpTED0ACyIjMe8/hkU+3yyLycxGR3zdPT08ZgPCv/z4U5Y/Fn+9OvlMaYIGcIoAKx+VnSjEAggKLSOR0XkZS1xQFICiwyodSUmOKARAUWGJR+fNXHZMCCMYbBsmhSimlaoN4LD/Fn+9OFpQMEEGBNY7Kz7yU1jFFAQgKkBQAgoKGnJafGcUACApCc7/hfz8ro6gpxQMICkKybRX5eyQFCAosI5K6pBgAQYFVPrOQExAUWOYrkgIEBdYlNaIYAEGBVaalpIYUAyAo6IOzhn9/tZATSQGCApMgKUBQgKQAQQEgKUBQYIuWxIKkAEFBJ7R1YgGSAgQFpHuAoCAdCiQFCAqIpAAQFASOoJAUIChojS6P9UVSgKDgIE5J9wBBgTl6lAaSAgQFjelTGEgKEBQ0ouj5960kxXlSgKDAVARVldTvnMwJm+Dqc8j0Us7/C/w1Lv58dzKlNoAICkKnd+vgjHNAULAWK+NAIqkJ1QEICqxFUCs+cTkorGAMKnF0qv8Pg1/",
        "image_catcher",
        "drupal-8.png",
      ],
    ];
  }

}
