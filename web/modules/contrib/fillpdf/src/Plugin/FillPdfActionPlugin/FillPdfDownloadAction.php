<?php

namespace Drupal\fillpdf\Plugin\FillPdfActionPlugin;

use Drupal\fillpdf\Plugin\FillPdfActionPluginBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Action plugin sending a generated PDF file to the users browser.
 *
 * @package Drupal\fillpdf\Plugin\FillPdfActionPlugin
 *
 * @FillPdfActionPlugin(
 *   id = "download",
 *   label = @Translation("Download PDF")
 * )
 */
class FillPdfDownloadAction extends FillPdfActionPluginBase {

  /**
   * Executes this plugin.
   *
   * Sends the PDF file to the user's browser.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Sends the PDF file to the browser.
   */
  public function execute() {
    $response = new Response($this->configuration['data']);

    // This ensures that the browser serves the file as a download.
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $this->configuration['filename']
    );
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
  }

}
