<?php

namespace Drupal\Tests\bibcite_marc\Kernel;

use Drupal\bibcite_marc\Encoder\MarcEncoder;
use Drupal\Tests\bibcite_import\Kernel\FormatDecoderTestBase;

/**
 * @coversDefaultClass \Drupal\bibcite_marc\Encoder\MarcEncoder
 * @group bibcite
 */
class MarcDecodeTest extends FormatDecoderTestBase {

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
    $this->resultDir = __DIR__ . '/../../data/decoded';
    $this->inputDir = __DIR__ . '/../../data/encoded';
  }

}
