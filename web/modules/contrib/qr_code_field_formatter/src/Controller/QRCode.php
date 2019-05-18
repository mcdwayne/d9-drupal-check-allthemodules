<?php
namespace Drupal\qr_code_field_formatter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\qr_code_field_formatter\Response\QRCodeResponse;

/**
 * Generate a QR Code from a provided string
 */
class QRCode extends ControllerBase {

  public function image($text){
    return new QRCodeResponse($text);
  }

}
