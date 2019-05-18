<?php

namespace Drupal\pdf_using_mpdf;

/**
 * Provides an interface defining methods needed for PDF generation.
 */
interface ConvertToPdfInterface {
  /**
   * Point of call to instantiate the mPDF library
   * and call the generator functions for creating a
   * PDF file.
   * 
   * @param string $html
   *   The html that will be converted into PDF content.
   * 
   * @return mixed
   */
  function convert($html);
}
