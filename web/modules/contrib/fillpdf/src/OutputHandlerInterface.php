<?php

namespace Drupal\fillpdf;

/**
 * Contains functions to standardize output handling for generated PDFs.
 *
 * @package Drupal\fillpdf
 */
interface OutputHandlerInterface {

  /**
   * Saves merged PDF data to the filesystem.
   *
   * @param array $configuration
   *   An array of configuration as originally passed from
   *   HandlePdfController::handlePopulatedPdf() to the FillPdfActionPlugin,
   *   containing the following properties:
   *     form: The FillPdfForm object from which the PDF was generated.
   *     context: The FillPDF request context as returned by
   *       FillPdfLinkManipulatorInterface::parseLink().
   *     token_objects: The token data from which the PDF was generated.
   *     data: The populated PDF data itself.
   *     filename: The filename (not including path) with which
   *       the PDF should be presented.
   * @param string $destination_path_override
   *   (optional) A destination path to override the one given by the
   *   FillPdfForm.
   *
   * @return \Drupal\file\FileInterface|false
   *   The file entity, or FALSE on error.
   *
   * @see \Drupal\fillpdf\FillPdfLinkManipulatorInterface::parseLink()
   * @see \Drupal\fillpdf\Plugin\FillPdfActionPlugin\FillPdfSaveAction::savePdf()
   *
   * @todo: Rename 'token_objects' to 'entities' in FillPDF 5.x. Webform
   *   submissions are now entities, too.
   */
  public function savePdfToFile(array $configuration, $destination_path_override = NULL);

}
