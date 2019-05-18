<?php

namespace Drupal\digitallocker_issuer;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class SignedPdf.
 *
 * @package Drupal\digitallocker_issuer
 */
class SignedPdf {

  /**
   * Given a node, generate the certificate.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node whose certificate is to be generated.
   *
   * @return string
   *   The string representation of the generated pdf.
   */
  public static function generate(EntityInterface $node) {
    /* @var \Drupal\tcpdf\TCPDFDrupal $pdf */

    $config = \Drupal::config('digitallocker_issuer.settings');
    $certificate = $config->get('certificate_path');
    $priv_key_pass = $config->get('certificate_pass');

    $info = [
      'Name' => $config->get('certificate_name'),
      'Reason' => $config->get('certificate_reason'),
      'Location' => $config->get('certificate_location'),
      'ContactInfo' => $config->get('certificate_contact'),
    ];

    $filename = tempnam(file_directory_temp(), 'dlpdf');
    $image = imagecreate(200, 50);
    imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    imagestring($image, 1, 2, 5, 'Digitally Signed by ' . $info['Name'], $black);
    imagestring($image, 1, 2, 20, ' on ' . date('d/m/y') . ' at ' . $info['Location'], $black);
    imagepng($image, $filename);
    imagedestroy($image);

    $options = [
      'title' => $node->getEntityTypeId(),
      'author' => $info['Name'],
      'subject' => '',
      'keywords' => '',
    ];

    $pdf = tcpdf_get_instance();
    $pdf->setPrintHeader(FALSE);

    $pdf->DrupalInitialize($options);
    $pdf->Image($filename, 145, 257, 0, 0, 'PNG');
    $pdf->setFooterMargin(20);
    \Drupal::moduleHandler()
      ->alter('digitallocker_issuer_document_' . $node->getEntityTypeId(), $pdf, $node);

    $pdf->setSignature($certificate, $certificate, $priv_key_pass, '', 1, $info);
    $pdf->deletePage($pdf->getPage());
    $pdf->setSignatureAppearance(150, 257, 50, 20, 1);

    return $pdf->Output('', 'S');
  }

}
