<?php

namespace Drupal\fillpdf;

/**
 * Interface SerializerInterface.
 *
 * @package Drupal\fillpdf
 */
interface SerializerInterface {

  /**
   * Serializes a FillPdfForm for export.
   *
   * @param \Drupal\fillpdf\FillPdfFormInterface $fillpdf_form
   *   The FillPdf Form to serialize.
   *
   * @return string
   *   The serialized FillPdfForm.
   */
  public function getFormExportCode(FillPdfFormInterface $fillpdf_form);

  /**
   * Deserializes a serialized FillPdfForm for import.
   *
   * @param string $code
   *   The serialized FillPdfForm.
   *
   * @return array
   *   Associative array containing the deserialized FillPdfForm object keyed
   *   with 'form' and an array of deserialized FillPdfFormField objects keyed
   *   with 'fields'.
   */
  public function deserializeForm($code);

  /**
   * Imports a FillPdfForm.
   *
   * @param \Drupal\fillpdf\FillPdfFormInterface $fillpdf_form
   *   The existing FillPdfForm.
   * @param \Drupal\fillpdf\FillPdfFormInterface $imported_form
   *   The existing FillPdfForm.
   * @param \Drupal\fillpdf\FillPdfFormFieldInterface[] $imported_fields
   *   Array of FillPdfFormField objects to import.
   *
   * @return string[]
   *   Array of unmatched PDF keys.
   */
  public function importForm(FillPdfFormInterface $fillpdf_form, FillPdfFormInterface $imported_form, array $imported_fields);

  /**
   * Imports FillPdfFormFields.
   *
   * Overwrites empty field values imported from export code with previous
   * existing values.
   *
   * @param \Drupal\fillpdf\FillPdfFormFieldInterface[] $keyed_fields
   *   Associative array of unsaved FillPdfFormField objects keyed by PDF key.
   * @param string[] $existing_fields
   *   (optional) Array of existing PDF keys.
   *
   * @return string[]
   *   Array of unmatched PDF keys.
   */
  public function importFormFields(array $keyed_fields, array $existing_fields = []);

  /**
   * Overwrites empty new field values with previous existing values.
   *
   * @param \Drupal\fillpdf\FillPdfFormFieldInterface[] $form_fields
   *   Associative array of saved FillPdfFormField objects keyed by entity ID.
   * @param string[] $existing_fields
   *   (optional) Array of existing PDF keys.
   *
   * @return string[]
   *   Array of unmatched PDF keys.
   *
   * @see \Drupal\fillpdf\SerializerInterface::importFormFields()
   */
  public function importFormFieldsByKey(array $form_fields, array $existing_fields = []);

}
