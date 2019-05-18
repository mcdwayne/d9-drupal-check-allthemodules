<?php

namespace Drupal\Tests\bibcite_ris\Kernel;

use Drupal\bibcite_ris\Encoder\RISEncoder;
use Drupal\Tests\bibcite_export\Kernel\FormatEncoderTestBase;

/**
 * @coversDefaultClass \Drupal\bibcite_ris\Encoder\RISEncoder
 * @group bibcite
 */
class RisEncodeTest extends FormatEncoderTestBase {

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
    $this->encodedExtension = 'ris';
    $this->resultDir = __DIR__ . '/../../data/encoded';
    $this->inputDir = __DIR__ . '/../../data/decoded';
  }

  /**
   * Tests a pages encode.
   *
   * @coversDefaultClass
   */
  public function testPagesEncode() {
    $example = "TY - SER\nSP - 1\nEP - 3\nSP - 7\nEP - 9\nEP - 12\nSP - 19\nER - \n";

    $pages = '1-3,7-9,12,19+';
    $data = [0 => ['TY' => 'SER', 'SP' => $pages]];
    $encoder = new RISEncoder();
    $result = $encoder->encode($data, 'ris');
    $this->assertEquals($example, $result);
  }

}
