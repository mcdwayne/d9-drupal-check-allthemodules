<?php

namespace Drupal\qr_code_field_formatter\Response;

use \PHPQRCode\QRcode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response which is returned as the captcha for image_captcha.
 *
 * @package Drupal\image_captcha\Response
 */
class QRCodeResponse extends Response {

  /**
   * Resource with generated image.
   *
   * @var resource
   */
  protected $image;

  protected $data;

  /**
   * {@inheritdoc}
   */
  public function __construct($content = '', int $status = 200, array $headers = []) {
    parent::__construct(NULL, $status, $headers); 
    $this->data = $content;
  }
  
  /**
   * {@inheritdoc}
   */
  public function prepare(Request $request) {
    $this->image = @$this->generateImage($this->data);

    return parent::prepare($request);
  }

  /**
   * {@inheritdoc}
   */
  public function sendHeaders() {
    $this->headers->set('content-type', 'image/png');

    return parent::sendHeaders();
  }

  /**
   * {@inheritdoc}
   */
  public function sendContent() {
    if (!$this->image) {
      return;
    }

    // Begin capturing the byte stream.
    ob_start();

    imagepng($this->image);
    // Clean up the image resource.
    imagedestroy($this->image);
  }


  /**
   * Base function for generating a QR Code.
   *
   * @param string $text
   *   String to be turned into a QR Code.
   *
   * @return resource
   *   Image to be outputted containing $text string.
   */
  protected function generateImage($text) {
    return QRcode::png($text);
  }

}
