<?php

namespace Drupal\Tests\bibcite_import\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for encode function testing.
 *
 * Class FormatEncoderTestBase.
 *
 * @package Drupal\Tests\bibcite_export\Kernel
 */
abstract class FormatDecoderTestBase extends KernelTestBase {

  /**
   * Encoder instance to test.
   *
   * @var \Symfony\Component\Serializer\Encoder\DecoderInterface
   */
  protected $encoder;

  protected $resultDir;

  protected $inputDir;

  protected $format;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->formatManager = $this->container->get('plugin.manager.bibcite_format');
    $this->serializer = $this->container->get('serializer');
  }

  /**
   * Tests a files decode.
   *
   * @coversDefaultClass
   */
  public function testDecode() {
    $input = scandir($this->inputDir);
    foreach ($input as $file) {
      if (is_file($this->inputDir . '/' . $file)) {
        $info = pathinfo($file);
        $file_name = basename($file, '.' . $info['extension']);
        $result_file = $file_name . '.json';
        if (file_exists($this->resultDir . '/' . $result_file)) {
          $encoded_source = file_get_contents($this->inputDir . '/' . $file);
          $expected = json_decode(file_get_contents($this->resultDir . '/' . $result_file), TRUE);
          $decoded_source = json_decode(json_encode($this->encoder->decode($encoded_source, $this->format)), TRUE);
          $this->assertEquals($expected, $decoded_source);
        }
        else {
          $this->fail('Result file not exist for ' . $file);
        }
      }
    }
    if (!$this->getCount()) {
      $this->fail('No assertions in ' . __FUNCTION__);
    }
  }

}
