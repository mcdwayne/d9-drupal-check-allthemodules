<?php

namespace Drupal\rut\Tests;

use Drupal\rut\Rut;
use Drupal\simpletest\KernelTestBase;

/**
 * Test that class Rut works properly.
 *
 * @group rut
 */
class RutTest extends KernelTestBase {

  /**
   * Unit test of class Rut.
   */
  public function testRutTestFunction() {
    $rut = '11.111.111-1';
    $separate = Rut::separateRut($rut);

    $message = "Test the separate rut with $rut. Must be return an array with two elements";
    $this->assertTrue(count($separate) == 2, $message);

    $message = "The first element must be '11111111'.";
    $this->assertTrue($separate[0] == '11111111', $message);

    $message = "The second element must be '1'.";
    $this->assertTrue($separate[1] == '1', $message);

    $_rut = '111111111';
    $must_be = '11.111.111-1';
    $message = "Valid the formatter rut from $_rut to $must_be";
    list($rut, $dv) = Rut::separateRut($_rut);
    $this->assertTrue(Rut::formatterRut($rut, $dv) == $must_be, $message);

    // Test the validate rut.
    $valid_ruts = [
      '1-9',
      '11.111.111-1',
    ];
    $invalid_ruts = [
      '11.111.111-2',
      '43.455.562-1',
    ];

    $message = 'Check a valid Rut: ';
    foreach ($valid_ruts as $_rut) {
      list($rut, $dv) = Rut::separateRut($_rut);
      $this->assertTrue(Rut::validateRut($rut, $dv), $message . $_rut);
    }

    $message = 'Check a invalid Rut: ';
    foreach ($invalid_ruts as $_rut) {
      list($rut, $dv) = Rut::separateRut($_rut);
      $this->assertFalse(Rut::validateRut($rut, $dv), $message . $_rut);
    }

    $message = 'Check a valid Rut generated with the method generateRut: ';
    for ($i = 0; $i < 5; $i++) {
      $_rut = Rut::generateRut(TRUE, 10000000, 70000000);
      list($rut, $dv) = Rut::separateRut($_rut);
      $this->assertTrue(Rut::validateRut($rut, $dv), $message . $_rut);
    }
  }
}
