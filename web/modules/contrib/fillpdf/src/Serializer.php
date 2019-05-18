<?php

namespace Drupal\fillpdf;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

/**
 * Class Serializer.
 *
 * @package Drupal\fillpdf
 */
class Serializer implements SerializerInterface {

  /**
   * Symfony\Component\Serializer\Serializer definition.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Serializer object.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The FillPdf Form to serialize.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(SymfonySerializer $serializer, EntityTypeManagerInterface $entity_type_manager) {
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormExportCode(FillPdfFormInterface $fillpdf_form) {
    $fields = $fillpdf_form->getFormFields();

    $form_config = [
      'form' => $this->serializer->normalize($fillpdf_form),
      'fields' => $this->serializer->normalize($fields),
    ];

    $code = $this->serializer->serialize($form_config, 'json');
    return $code;
  }

  /**
   * {@inheritdoc}
   */
  public function deserializeForm($code) {
    $mappings_raw = json_decode($code, TRUE);
    $decoded_fillpdf_form = $this->serializer->denormalize($mappings_raw['form'], 'Drupal\fillpdf\Entity\FillPdfForm');

    // Denormalization is a pain; we have to iterate over the fields to actually
    // recompose the $fields array.
    $field_json = $mappings_raw['fields'];
    $decoded_fields = [];

    foreach ($field_json as $normalized_field) {
      $field = $this->serializer->denormalize($normalized_field, 'Drupal\fillpdf\Entity\FillPdfFormField');
      $decoded_fields[$field->pdf_key->value] = $field;
    }

    $return = ['form' => $decoded_fillpdf_form, 'fields' => $decoded_fields];
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function importForm(FillPdfFormInterface $fillpdf_form, FillPdfFormInterface $imported_form, array $imported_fields) {
    // Key the existing FillPDF fields on PDF keys.
    $existing_fields = $fillpdf_form->getFormFields();

    // Iterate over FillPdfForm fields and copy them, EXCEPT for IDs and
    // references.
    $fillpdf_form_type = $this->entityTypeManager->getDefinition('fillpdf_form');
    $form_fields_to_ignore = array_filter(array_values($fillpdf_form_type->getKeys()));
    $form_fields_to_ignore[] = 'file';
    foreach ($imported_form->getFields() as $name => $data) {
      if (!in_array($name, $form_fields_to_ignore, TRUE)) {
        $fillpdf_form->{$name} = $data;
      }
    }
    $fillpdf_form->save();
    $unmatched_pdf_keys = $this->importFormFields($imported_fields, $existing_fields);

    return $unmatched_pdf_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function importFormFields(array $keyed_fields, array $existing_fields = []) {
    // Iterate over each FillPdfFormField and override matching PDF keys
    // (if any current fields have them).
    $fillpdf_field_type = $this->entityTypeManager->getDefinition('fillpdf_form_field');
    $field_fields_to_ignore = array_filter(array_values($fillpdf_field_type->getKeys()));
    $field_fields_to_ignore[] = 'fillpdf_form';

    $existing_fields_by_key = [];
    foreach ($existing_fields as $existing_field) {
      $existing_fields_by_key[$existing_field->pdf_key->value] = $existing_field;
    }

    $existing_field_pdf_keys = array_keys($existing_fields_by_key);
    $unmatched_pdf_keys = [];

    foreach ($keyed_fields as $pdf_key => $keyed_field) {
      // If the imported field's PDF key matching the PDF key of the
      // existing field, then copy the constituent entity fields.
      // I know: they're both called fields. It's confusing as hell.
      // I am sorry.
      if (in_array($pdf_key, $existing_field_pdf_keys, TRUE)) {
        $existing_field_by_key = $existing_fields_by_key[$pdf_key];
        foreach ($keyed_field->getFields() as $keyed_field_name => $keyed_field_data) {
          if (!in_array($keyed_field_name, $field_fields_to_ignore, TRUE)) {
            $existing_field_by_key->{$keyed_field_name} = $keyed_field_data;
          }
        }
        $existing_field_by_key->save();
      }
      else {
        $unmatched_pdf_keys[] = $pdf_key;
      }
    }
    return $unmatched_pdf_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function importFormFieldsByKey(array $form_fields, array $existing_fields = []) {
    // Key form fields by PDF key, then pass to ::importFormFields().
    $keyed_fields = [];
    foreach ($form_fields as $form_field) {
      $keyed_fields[$form_field->pdf_key->value] = $form_field;
    }

    return $this->importFormFields($keyed_fields, $existing_fields);
  }

}
