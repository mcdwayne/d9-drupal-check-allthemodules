<?php

namespace Drupal\fillpdf;

use Drupal\file\FileInterface;

/**
 * Interface InputHelperInterface.
 *
 * @package Drupal\fillpdf
 */
interface InputHelperInterface {

  public function attachPdfToForm(FileInterface $file, FillPdfFormInterface $existing_form = NULL);

}
