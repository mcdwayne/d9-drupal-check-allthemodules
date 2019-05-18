<?php

namespace Drupal\fillpdf\FieldMapping;

use Drupal\fillpdf\FieldMapping;

final class TextFieldMapping extends FieldMapping {

  /**
   * @var string
   */
  protected $data;

  public function __construct($data) {
    // Ensure data is a string.
    parent::__construct((string) $data);
  }

  /**
   * @return string
   */
  public function getData() {
    return parent::getData();
  }

}
