<?php

namespace Drupal\Tests\bibcite_crossref\Kernel;

use Drupal\bibcite_crossref\Encoder\CrossrefEncoder;
use Drupal\Tests\bibcite_import\Kernel\FormatDecoderTestBase;

/**
 * @coversDefaultClass \Drupal\bibcite_crossref\Encoder\CrossrefEncoder
 * @group bibcite
 */
class CrossrefDecodeTest extends FormatDecoderTestBase {

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
    'bibcite_crossref',
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
      'bibcite_crossref',
    ]);

    $this->encoder = new CrossrefEncoder();
    $this->format = 'crossref';
    $this->resultDir = __DIR__ . '/../../data/decoded';
    $this->inputDir = __DIR__ . '/../../data/encoded';
  }

}
