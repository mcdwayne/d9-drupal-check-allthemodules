<?php

namespace Drupal\excel_libxl\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Adds XLS encoder support via LibXL library for the Serialization API.
 */
class XlsLibXl extends XlsxLibXl implements EncoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $format = 'xls';

  /**
   * Format to write XLS files as.
   *
   * @var string
   */
  protected $xlsFormat = 'Excel5';

  /**
   * Constructs an XLS encoder.
   *
   * @param string $xls_format
   *   The XLS format to use.
   */
  public function __construct($xls_format = 'Excel5') {
    parent::__construct();
    $this->xlsFormat = $xls_format;
  }

}
