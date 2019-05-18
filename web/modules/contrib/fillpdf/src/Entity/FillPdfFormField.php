<?php

namespace Drupal\fillpdf\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\fillpdf\FillPdfFormFieldInterface;
use Drupal\fillpdf\Service\FillPdfAdminFormHelper;

/**
 * Defines an entity for PDF fields associated with a FillPDF form entity.
 *
 * Uses the same access handler as fillpdf_form.
 *
 * @ContentEntityType(
 *   id = "fillpdf_form_field",
 *   label = @Translation("FillPDF form field"),
 *   handlers = {
 *     "views_data" = "Drupal\fillpdf\FillPdfFormFieldViewsData",
 *     "form" = {
 *       "edit" = "Drupal\fillpdf\Form\FillPdfFormFieldForm",
 *     },
 *     "access": "Drupal\fillpdf\FillPdfFormAccessControlHandler",
 *     "storage" = "Drupal\fillpdf\FillPdfFormFieldStorage",
 *   },
 *   admin_permission = "administer pdfs",
 *   base_table = "fillpdf_fields",
 *   entity_keys = {
 *     "id" = "ffid",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class FillPdfFormField extends ContentEntityBase implements FillPdfFormFieldInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['ffid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('FillPDF Form Field ID'))
      ->setDescription(t('The ID of the FillPdfFormField entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['fillpdf_form'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('FillPDF Form ID'))
      ->setDescription(t('The ID of the FillPdfFormField entity.'))
      ->setSetting('target_type', 'fillpdf_form');

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the FillPdfFormField entity.'))
      ->setReadOnly(TRUE);

    $fields['pdf_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('PDF Key'))
      ->setDescription(t('The name of the field in the PDF form.'));

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('PDF field label'))
      ->setDescription(t('An optional label to help you identify the field.'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['prefix'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Text before field value'))
      ->setDescription(t('Text to add to the front of the field value unless the field value is blank.'))
      ->setDisplayOptions('form', [
        'type' => 'string_long',
        'weight' => 0,
      ]);

    $fields['value'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Fill pattern'))
      ->setDescription(t('Text and tokens with which to fill in the PDF. This field supports tokens.'))
      ->setDisplayOptions('form', [
        'type' => 'string_long',
        'weight' => 0,
      ]);

    $fields['suffix'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Text after field value'))
      ->setDescription(t('Text to add to the end of the field value unless the field value is blank.'))
      ->setDisplayOptions('form', [
        'type' => 'string_long',
        'weight' => 0,
      ]);

    $fields['replacements'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Change text before sending to PDF (Transform values)'))
      ->setDescription(FillPdfAdminFormHelper::getReplacementsDescription())
      ->setDisplayOptions('form', [
        'type' => 'string_long',
        'weight' => 0,
      ]);

    return $fields;
  }

}
