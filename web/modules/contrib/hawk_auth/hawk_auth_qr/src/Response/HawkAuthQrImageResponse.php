<?php

/**
 * @file
 * Contains \Drupal\hawk_auth_qr\Response\HawkAuthQrImageResponse.
 */

namespace Drupal\hawk_auth_qr\Response;

use Drupal\hawk_auth\Entity\HawkCredentialInterface;
use Endroid\QrCode\QrCode;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for Hawk Auth QR, primary purpose is to generate and display
 * QR codes for individual credentials.
 */
class HawkAuthQrImageResponse extends Response {

  /**
   * The credential we are generating a response for.
   *
   * @var HawkCredentialInterface
   */
  protected $credential;

  /**
   * Qr library.
   *
   * @var QrCode
   */
  protected $qrCode;

  /**
   * Constructs this class' object.
   *
   * @param HawkCredentialInterface $credential
   *   The credential to generate a response for
   * @param QrCode $qr_code
   *   Library to generate QR Codes.
   *
   * {@inheritdoc}
   */
  public function __construct(HawkCredentialInterface $credential, QrCode $qr_code, $content = '', $status = 200, $headers = []) {
    parent::__construct($content, $status, $headers);

    $this->credential = $credential;
    $this->qrCode = $qr_code;
  }

  /**
   * {@inheritdoc}
   */
  public function sendHeaders() {
    $this->headers->set('content-type', 'image/png');
    parent::sendHeaders();
  }

  /**
   * {@inheritdoc}
   */
  public function sendContent() {
    $this->qrCode->setSize(300);
    $this->qrCode->setText(json_encode([
      'id' => $this->credential->id(),
      'key' => $this->credential->getKeySecret(),
      'algo' => $this->credential->getKeyAlgo(),
    ]));
    $this->qrCode->setLabel(t('Hawk Credential #!id: !name', [
      '!id' => $this->credential->id(),
      '!name' => $this->credential->getOwner()->getUsername()
    ]));
    $this->qrCode->render();
  }

}
