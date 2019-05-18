<?php

namespace Drupal\Tests\bibcite_ris\Kernel;

use Drupal\bibcite_ris\Encoder\RISEncoder;
use Drupal\Tests\bibcite_import\Kernel\FormatDecoderTestBase;

/**
 * @coversDefaultClass \Drupal\bibcite_ris\Encoder\RISEncoder
 * @group bibcite
 */
class RisDecodeTest extends FormatDecoderTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'serialization',
    'bibcite',
    'bibcite_entity',
    'bibcite_ris',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig([
      'system',
      'user',
      'serialization',
      'bibcite',
      'bibcite_entity',
      'bibcite_ris',
    ]);

    $this->encoder = new RISEncoder();
    $this->format = 'ris';
    $this->resultDir = __DIR__ . '/../../data/decoded';
    $this->inputDir = __DIR__ . '/../../data/encoded';
  }

  /**
   * Tests a pages decode.
   *
   * @coversDefaultClass
   */
  public function testPagesDecode() {
    $data = "TY - SER\nTI - test\nSP - 1\nEP - 3\nSP - 7\nEP - 9\nEP - 12\nSP - 19\nER - \n";

    $pages = '1-3, 7-9, 12, 19+';
    $example = [0 => ['TY' => 'SER', 'TI' => 'test', 'SP' => $pages, 'EP' => $pages]];
    $encoder = new RISEncoder();
    $result = $encoder->decode($data, 'ris');
    $this->assertEquals($example, $result);
  }

}
