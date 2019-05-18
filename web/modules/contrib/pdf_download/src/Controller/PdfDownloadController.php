<?php

namespace Drupal\pdf_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
   * {@inheritdoc}
   */
class PdfDownloadController extends ControllerBase {

  public function downloadPdf($entity_arr) {
    $pdf = $this->PdfDownloadSample($entity_arr);
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdf));
    header('Content-Disposition: attachment; filename="mydocument.pdf"');
    print $pdf;die;
  }
  protected function PdfDownloadSample($entity_arr) {
    $html_template = [
      '#theme' => 'pdf_content',
      '#pdf' => $entity_arr,
    ];
    $html = \Drupal::service('renderer')->render($html_template);
    $tcpdf = tcpdf_get_instance();
    $tcpdf->DrupalInitialize([
      'footer' => [
        'html' => 'This is a Footer!! <em>Footer of the page</em>',
      ],
      'header' => [
        'callback' => [
          'function' => 'pdf_download_default_header',
          // You can pass extra data to your callback.
          'context' => [
            'welcome_message' => 'Hello, PDF Download example!',
          ],
        ],
      ],
    ]);
    $tcpdf->writeHTML($html);
    return $tcpdf->Output('', 'S');
  }

}
