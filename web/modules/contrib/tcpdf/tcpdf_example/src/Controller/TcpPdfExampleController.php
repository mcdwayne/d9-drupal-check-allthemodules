<?php

/**
 * @file
 * Contains \Drupal\tcpdf_example\Controller\TcpPdfExampleController
 */

namespace Drupal\tcpdf_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class TcpPdfExampleController extends ControllerBase {


  public function downloadPdf($example_name) {
    switch ($example_name) {
      case 'simple':
        $pdf = $this->generateSimplePdf();
        break;
      default:
        return $this->t('No such example.');
    }

    // Tell the browser that this is not an HTML file to show, but a pdf file to
    // download.
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdf));
    header('Content-Disposition: attachment; filename="mydocument.pdf"');
    print $pdf;
    return [];
  }

  public function exampleContents() {
    $page = [];

    $page['example_pdf_link'] = [
      '#title' => $this->t('Basic pdf'),
      '#type' => 'link',
      '#url' => Url::fromRoute('tcpdf_example.download_pdf', array('example_name' => 'simple'))
    ];

    return $page;
  }

  /**
   * Generates a pdf file using TCPDF module.
   *
   * @return string Binary string of the generated pdf.
   */
  protected function generateSimplePdf() {
    // Get the content we want to convert into pdf.

    $html_template = [
      '#theme' => 'tcpdf_example_basic_html',
    ];
    $html = \Drupal::service('renderer')->render($html_template);

    // Never make an instance of TCPDF or TCPDFDrupal classes manually.
    // Use tcpdf_get_instance() instead.
    $tcpdf = tcpdf_get_instance();
    /* DrupalInitialize() is an extra method added to TCPDFDrupal that initializes
    *  some TCPDF variables (like font types), and makes possible to change the
    *  default header or footer without creating a new class.
    */
    $tcpdf->DrupalInitialize(array(
      'footer' => array(
        'html' => 'This is a test!! <em>Bottom of the page</em>',
      ),
      'header' => array(
        'callback' => array(
          'function' => 'tcpdf_example_default_header',
          // You can pass extra data to your callback.
          'context' => array(
            'welcome_message' => 'Hello, tcpdf example!',
          ),
        ),
      ),
    ));
    // Insert the content. Note that DrupalInitialize automatically adds the first
    // page to the pdf document.
    $tcpdf->writeHTML($html);

    return $tcpdf->Output('', 'S');
  }
}
