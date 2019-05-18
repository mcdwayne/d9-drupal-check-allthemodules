<?php

namespace Drupal\Tests\bibcite_endnote\Kernel;

use Drupal\bibcite_endnote\Encoder\EndnoteEncoder;
use Drupal\Tests\bibcite_import\Kernel\FormatDecoderTestBase;

/**
 * @coversDefaultClass \Drupal\bibcite_endnote\Encoder\EndnoteEncoder
 * @group bibcite
 */
class Endnote7DecodeTest extends FormatDecoderTestBase {

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
    $this->format = 'endnote7';
    $this->resultDir = __DIR__ . '/../../data/decoded/en7';
    $this->inputDir = __DIR__ . '/../../data/encoded/en7';
  }

}
