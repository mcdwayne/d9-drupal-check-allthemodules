<?php

namespace Drupal\fillpdf\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\fillpdf\FillPdfFileContextInterface;

/**
 * Defines the FillPDF file context entity.
 *
 * @ingroup fillpdf
 *
 * @ContentEntityType(
 *   id = "fillpdf_file_context",
 *   label = @Translation("FillPDF file context"),
 *   handlers = {
 *     "views_data" = "Drupal\fillpdf\Entity\FillPdfFileContextViewsData",
 *     "access" = "Drupal\fillpdf\FillPdfFileContextAccessControlHandler",
 *   },
 *   base_table = "fillpdf_file_context",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class FillPdfFileContext extends ContentEntityBase implements FillPdfFileContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the FillPDF file context entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the FillPDF file context entity.'))
      ->setReadOnly(TRUE);

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('The associated managed file.'))
      ->setDescription(t('The associated managed file.'));

    $fields['context'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Generation context'))
      ->setDescription(t("The normalized, root-relative, stringified FillPDF Link (URL) that was used to generate the PDF. This field is fairly forgiving and will work with and without base URLs, and, if provided, the base URLs don't have to match the current site."))
      ->setRequired(TRUE);

    return $fields;
  }

}
