<?php

namespace Drupal\Tests\bibcite_bibtex\Kernel;

use Drupal\bibcite_bibtex\Encoder\BibtexEncoder;
use Drupal\Tests\bibcite_import\Kernel\FormatDecoderTestBase;

/**
 * @coversDefaultClass \Drupal\bibcite_bibtex\Encoder\BibtexEncoder
 * @group bibcite
 */
class BibtexCaseDecodeTest extends FormatDecoderTestBase {

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
    $this->resultDir = __DIR__ . '/../../data/decoded/case';
    $this->inputDir = __DIR__ . '/../../data/encoded/case';
  }

}
