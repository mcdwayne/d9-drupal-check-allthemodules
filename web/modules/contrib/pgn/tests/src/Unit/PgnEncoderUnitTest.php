<?php

/**
 * @file
 * Contains \Drupal\Tests\pgn\Unit\PgnEncoderUnitTest.
 */

namespace Drupal\Tests\pgn\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\pgn\Serializer\Encoder\PgnEncoder;

/**
 * Tests that PGN Encoder encodes into PGN.
 *
 * @group pgn
 */
class PgnEncoderUnitTest extends UnitTestCase {

  protected $encoder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->encoder = new PgnEncoder();
  }

  /**
   * @test
   * @dataProvider providerPgnEncode
   */
  public function pgnEncode($normalized, $file) {
    $encoded = $this->encoder->encode($normalized, 'pgn');

    $expected = file_get_contents(dirname(dirname(__DIR__)) . '/' . $file);
    $this->assertEquals($expected, $encoded, 'Encoded PGN data is equal to expected PGN text.');
  }

  public function providerPgnEncode() {
    return array(
      array(
        array(
          array(
            'tags' => array(
              'Event' => '?',
              'Site' => '?',
              'Date' => '????.??.??',
              'Round' => '?',
              'White' => '?',
              'Black' => '?',
              'Result' => '*',
            ),
            'movetext' => array(
              1 => array('e4', 'f5'),
              2 => array('Qh5+'),
            ),
          ),
          array(
            'tags' => array(
              'Event' => '?',
              'Site' => '?',
              'Date' => '????.??.??',
              'Round' => '?',
              'White' => '?',
              'Black' => '?',
              'Result' => '*',
            ),
            'movetext' => array(
              1 => array('e4', 'f5'),
              2 => array('Qh5+'),
            ),
          ),
        ),
        'e4f5Dh5.pgn',
      ),
    );
  }

}
