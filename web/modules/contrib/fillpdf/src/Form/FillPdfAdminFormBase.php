<?php

namespace Drupal\fillpdf\Form;

use Drupal\Core\Form\FormBase;

@trigger_error('FillPdfAdminFormBase is deprecated in fillpdf:8.x-4.7 and will be removed from fillpdf:8.x-5.0. Use \Drupal\Core\Form\FormBase instead. See https://www.drupal.org/project/fillpdf/issues/3044743', E_USER_DEPRECATED);

/**
 * Class FillPdfAdminFormBase.
 *
 * @package Drupal\fillpdf\Form
 *
 * @deprecated in fillpdf:8.x-4.7 and will be removed from fillpdf:8.x-5.0. Use
 *   FormBase instead.
 * @see https://www.drupal.org/project/fillpdf/issues/3044743
 * @see \Drupal\Core\Form\FormBase
 */
abstract class FillPdfAdminFormBase extends FormBase {

  /**
   * FillPdfAdminFormBase constructor.
   */
  public function __construct() {

  }

}
