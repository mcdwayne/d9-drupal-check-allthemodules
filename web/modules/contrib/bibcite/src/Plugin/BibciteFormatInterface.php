<?php

namespace Drupal\bibcite\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;

/**
 * Defines interface for BibciteFormat wrapper.
 */
interface BibciteFormatInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Get list of format types.
   *
   * @return array
   *   List of format types.
   */
  public function getTypes();

  /**
   * Get list of format fields.
   *
   * @return array
   *   List of format fields.
   */
  public function getFields();

  /**
   * Get format label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Format label.
   */
  public function getLabel();

  /**
   * Get format file extension.
   *
   * @return string
   *   Format extension.
   */
  public function getExtension();

  /**
   * Check if current format is available for export.
   *
   * @return bool
   *   TRUE if format available for export, FALSE if not.
   */
  public function isExportFormat();

  /**
   * Check if current format is available for import.
   *
   * @return bool
   *   TRUE if format available for import, FALSE if not.
   */
  public function isImportFormat();

}
