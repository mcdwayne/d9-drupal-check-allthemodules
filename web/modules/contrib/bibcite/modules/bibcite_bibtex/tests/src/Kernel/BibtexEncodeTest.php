<?php

namespace Drupal\Tests\bibcite_bibtex\Kernel;

use Drupal\bibcite_bibtex\Encoder\BibtexEncoder;
use Drupal\Tests\bibcite_export\Kernel\FormatEncoderTestBase;

/**
 * @coversDefaultClass \Drupal\bibcite_bibtex\Encoder\BibtexEncoder
 * @group bibcite
 */
class BibtexEncodeTest extends FormatEncoderTestBase {

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
    'bibcite_bibtex',
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
      'bibcite_bibtex',
    ]);

    $this->encoder = new BibtexEncoder();
    $this->format = 'bibtex';
    $this->encodedExtension = 'bib';
    $this->resultDir = __DIR__ . '/../../data/encoded';
    $this->inputDir = __DIR__ . '/../../data/decoded';
  }

}
