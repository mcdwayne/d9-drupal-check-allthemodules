<?php

namespace Drupal\fillpdf\Plugin\FillPdfActionPlugin;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Action plugin redirecting to a generated PDF file saved to the filesystem.
 *
 * @package Drupal\fillpdf\Plugin\FillPdfActionPlugin
 *
 * @FillPdfActionPlugin(
 *   id = "redirect",
 *   label = @Translation("Redirect PDF to file")
 * )
 */
class FillPdfRedirectAction extends FillPdfSaveAction {

  /**
   * Executes this plugin.
   *
   * Saves the PDF file to the filesystem and redirects to it.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects user to the generated PDF file, or if saving the file fails,
   *   to the front page.
   *
   * @todo Replace file_create_url() by File::createFileUrl() once we're not
   *   supporting Drupal 8.6 anymore.
   */
  public function execute() {
    $saved_file = $this->savePdf();
    $url = ($saved_file !== FALSE) ? file_create_url($saved_file->getFileUri()) : Url::fromRoute('<front>')->toString();
    return new RedirectResponse($url);
  }

}
