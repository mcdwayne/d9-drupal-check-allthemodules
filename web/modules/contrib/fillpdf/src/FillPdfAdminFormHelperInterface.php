<?php

namespace Drupal\fillpdf;

/**
 * Interface FillPdfAdminFormHelperInterface.
 *
 * @package Drupal\fillpdf
 */
interface FillPdfAdminFormHelperInterface {

  /**
   * Returns render array for a link to a token tree shown as a dialog.
   *
   * @param string[]|string $token_types
   *   (optional) Array of token types. Defaults to 'all'.
   *
   * @return array
   *   Render array.
   */
  public function getAdminTokenForm($token_types = 'all');

  /**
   * Returns available file storage options for use with FAPI radio buttons.
   *
   * Any visible, writeable wrapper can potentially be used.
   *
   * @param array $label_templates
   *   (optional) Associative array of label templates keyed by scheme name.
   *
   * @return array
   *   Stream wrapper descriptions, keyed by scheme.
   */
  public function schemeOptions(array $label_templates = []);

  /**
   * Returns all FillPdfForms with template PDFs stored in a particular scheme.
   *
   * @return string
   *   Scheme of the templates PDFs.
   */
  public function getFormsByTemplateScheme($scheme);

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public static function getReplacementsDescription();

  /**
   * Returns the configured path to the local pdftk installation.
   *
   * @return string
   *   The configured path to the local pdftk installation.
   */
  public function getPdftkPath();

}
