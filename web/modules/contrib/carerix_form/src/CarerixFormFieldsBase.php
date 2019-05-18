<?php

namespace Drupal\carerix_form;

/**
 * Class CarerixFormBuilder.
 *
 * @package Drupal\carerix_form
 */
abstract class CarerixFormFieldsBase {

  /**
   * Form field types.
   */
  const FORM_FIELD_TYPE_FIELDGROUP = 'fieldgroup';
  const FORM_FIELD_TYPE_TEXTFIELD = 'textfield';
  const FORM_FIELD_TYPE_EMAIL = 'email';
  const FORM_FIELD_TYPE_TEL = 'tel';
  const FORM_FIELD_TYPE_URL = 'url';
  const FORM_FIELD_TYPE_TEXTAREA = 'textarea';
  const FORM_FIELD_TYPE_TEXTFORMAT = 'text_format';
  const FORM_FIELD_TYPE_PASSWORD = 'password';
  const FORM_FIELD_TYPE_FILE = 'managed_file';
  const FORM_FIELD_TYPE_DATE = 'date';

  /**
   * @var
   */
  protected $config;

  /**
   * CarerixFormFieldsBase constructor.
   */
  public function __construct() {
    $this->config = \Drupal::config('carerix_form.settings');
  }

  /**
   * Array of form fields.
   *
   * @return array
   *   All form fields.
   */
  public function getAll() {
    return $this->config->get('formfields');
  }

}
