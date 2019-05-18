<?php

namespace Drupal\digitallocker_issuer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\digitallocker_issuer\SignedPdf;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class DownloadPdfController.
 *
 * @package Drupal\digitallocker_issuer\Controller
 */
class DownloadPdfController extends ControllerBase {

  /**
   * Given a node, download the certificate corresponding to it.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node whose certificate is to be downloaded.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The file to be downloaded.
   */
  public function downloadPdf(NodeInterface $node) {
    $content = SignedPdf::generate($node);
    $path = tempnam(file_directory_temp(), 'dlpdf');
    file_put_contents($path, $content);

    return (new BinaryFileResponse($path))
      ->setContentDisposition('attachment', preg_replace('/[^a-zA-Z0-9\.\-\s]/', '', $node->getTitle()) . '.pdf')
      ->sendContent();
  }

}
