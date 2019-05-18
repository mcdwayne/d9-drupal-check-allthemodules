<?php

namespace Drupal\Tests\bibcite_marc\Kernel;

use Drupal\bibcite_marc\Encoder\MarcEncoder;
use Drupal\Tests\bibcite_export\Kernel\FormatEncoderTestBase;

/**
 * @coversDefaultClass \Drupal\bibcite_marc\Encoder\MarcEncoder
 * @group bibcite
 */
class MarcEncodeTest extends FormatEncoderTestBase {

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
    'bibcite_marc',
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
      'bibcite_marc',
    ]);

    $this->encoder = new MarcEncoder();
    $this->format = 'marc';
    $this->encodedExtension = 'mrc';
    $this->resultDir = __DIR__ . '/../../data/encoded';
    $this->inputDir = __DIR__ . '/../../data/decoded';
  }

}
