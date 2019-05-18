<?php
namespace Drupal\beer_o_clock\Unit;

use Drupal\Tests\UnitTestCase;



/**
 * Tests content translation local tasks.
 *
 * @group beer_o_clock
 */

class BeerOClockTest extends UnitTestCase {
  /**
  * @dataProvider additionProvider
  */
  public function testAdd($a, $b, $expected)
  {
    $this->assertEquals($expected, $a + $b);
  }

  public function additionProvider()
  {
    return array(
      array(0, 0, 0),
      array(0, 1, 1),
      array(1, 0, 1),
      array(1, 1, 3)
    );
  }
}
?>
