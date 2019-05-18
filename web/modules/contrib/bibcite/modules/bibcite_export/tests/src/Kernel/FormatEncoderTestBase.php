<?php

namespace Drupal\Tests\bibcite_export\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for encode function testing.
 *
 * Class FormatEncoderTestBase.
 *
 * @package Drupal\Tests\bibcite_export\Kernel
 */
abstract class FormatEncoderTestBase extends KernelTestBase {

  /**
   * Encoder instance to test.
   *
   * @var \Symfony\Component\Serializer\Encoder\EncoderInterface
   */
  protected $encoder;

  protected $format;

  protected $encodedExtension;

  protected $resultDir;

  protected $inputDir;

  protected $formatManager;
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->formatManager = $this->container->get('plugin.manager.bibcite_format');
    $this->serializer = $this->container->get('serializer');
  }

  /**
   * Tests a files encode.
   *
   * @coversDefaultClass
   */
  public function testEncode() {
    $input = scandir($this->inputDir);
    foreach ($input as $file) {
      if (is_file($this->inputDir . '/' . $file)) {
        $info = pathinfo($file);
        $file_name = basename($file, '.' . $info['extension']);
        $result_file = $file_name . '.' . $this->encodedExtension;
        if (file_exists($this->resultDir . '/' . $result_file)) {
          $source_array = json_decode(file_get_contents($this->inputDir . '/' . $file), TRUE);
          $encoded_source = $this->encoder->encode($source_array, $this->format);
          $expected = file_get_contents($this->resultDir . '/' . $result_file);
          $this->assertEquals($expected, $encoded_source);
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
