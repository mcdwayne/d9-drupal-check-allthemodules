<?php

namespace Drupal\gated_file\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Gated file entity.
 *
 * @ingroup gated_file
 *
 * @ContentEntityType(
 *   id = "gated_file",
 *   label = @Translation("Gated file"),
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\gated_file\Entity\GatedFileRouteProvider",
 *     },
 *   },
 *   base_table = "gated_file",
 *   admin_permission = "administer gated file entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "fid" = "fid",
 *     "form" = "form"
 *   },
 *   links = {
 *     "canonical" = "/gated_file/form/{gated_file}"
 *   }
 * )
 */
class GatedFile extends ContentEntityBase implements GatedFileInterface {

  /**
   * {@inheritdoc}
   */
  public function getFid() {
    return $this->get('fid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFid($entity_id) {
    $this->set('fid', $entity_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->get('form')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setForm($form) {
    $this->set('form', $form);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('fid'))
      ->setDescription(t('The File ID where the file will be gated.'))
      ->setRequired(TRUE);
    $fields['form'] = BaseFieldDefinition::create('string')
      ->setLabel(t('form'))
      ->setDescription(t('The form that will be displayed before to download the file'));

    return $fields;
  }

}
