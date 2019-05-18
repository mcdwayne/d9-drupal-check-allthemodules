<?php

namespace Drupal\fillpdf;

/**
 * Defines the required interface for all FillPDF BackendService plugins.
 *
 * @package Drupal\fillpdf
 *
 * @todo: Implement PluginInspectionInterface, ConfigurablePluginInterface and update implementations accordingly.
 */
interface FillPdfBackendPluginInterface {

  /**
   * Parse a PDF and return a list of its fields.
   *
   * @param \Drupal\fillpdf\FillPdfFormInterface $fillpdf_form
   *   The PDF whose fields are going to be parsed.
   *
   * @return array
   *   An array of associative arrays. Each sub-array contains a 'name' key with
   *   the name of the field and a 'type' key with the type. These can be
   *   iterated over and saved by the caller.
   */
  public function parse(FillPdfFormInterface $fillpdf_form);

  /**
   * Populate a FillPDF form with field data.
   *
   * Formerly known as merging. Accept an array of PDF field keys and field
   * values and populate the PDF using them.
   *
   * @param \Drupal\fillpdf\FillPdfFormInterface $fillpdf_form
   *   The FillPdfForm referencing the file whose field values are going to be
   *   populated.
   * @param array $field_mapping
   *   An array of fields mapping PDF field keys to the values with which they
   *   should be replaced. Example array:
   *   @code
   *   [
   *     'values' => [
   *       'Field 1' => 'value',
   *       'Checkbox Field' => 'On',
   *     ],
   *     'images' => [
   *       'Image Field 1' => [
   *         'data' => base64_encode($file_data),
   *         'filenamehash' => md5($image_path_info['filename']) . '.' . $image_path_info['extension'],
   *       ],
   *     ],
   *   ]
   *   @endcode
   * @param array $context
   *   The request context as returned by
   *   FillPdfLinkManipulatorInterface::parseLink().
   *
   * @return string|null
   *   The raw file contents of the new PDF, or NULL if populating failed. The
   *   caller has to handle saving or serving the file accordingly.
   *
   * @see \Drupal\fillpdf\FillPdfLinkManipulatorInterface::parseLink()
   */
  public function populateWithFieldData(FillPdfFormInterface $fillpdf_form, array $field_mapping, array $context);

}
