<?php

namespace Drupal\Tests\bibcite_endnote\Kernel;

use Drupal\bibcite_endnote\Encoder\EndnoteEncoder;
use Drupal\Tests\bibcite_import\Kernel\FormatDecoderTestBase;

/**
 * @coversDefaultClass \Drupal\bibcite_endnote\Encoder\EndnoteEncoder
 * @group bibcite
 */
class Endnote8DecodeTest extends FormatDecoderTestBase {

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
    'bibcite_endnote',
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
      'bibcite_endnote',
    ]);

    $this->encoder = new EndnoteEncoder();
    $this->format = 'endnote8';
    $this->resultDir = __DIR__ . '/../../data/decoded/en8';
    $this->inputDir = __DIR__ . '/../../data/encoded/en8';
  }

}
