<?php
/**
 * @file
 * Contains \Drupal\spectra\Entity\SpectraData.
 */

namespace Drupal\spectra\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\spectra\Controller\SpectraController;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the SpectraData entity.
 *
 * @ingroup spectra
 *
 *
 * @ContentEntityType(
 * id = "spectra_data",
 * label = @Translation("Spectra Data"),
 * handlers = {
 *   "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   "list_builder" = "Drupal\spectra\Entity\Controller\SpectraDataListBuilder",
 *   "views_data" = "Drupal\spectra\Entity\Views\SpectraDataViewsData",
 *   "form" = {
 *     "add" = "Drupal\spectra\Form\SpectraDataForm",
 *     "edit" = "Drupal\spectra\Form\SpectraDataForm",
 *     "delete" = "Drupal\spectra\Form\SpectraDataDeleteForm",
 *   },
 *   "access" = "Drupal\spectra\SpectraDataAccessControlHandler",
 * },
 * base_table = "spectra_data",
 * admin_permission = "administer spectra_data entity",
 * fieldable = TRUE,
 * entity_keys = {
 *   "id" = "data_id",
 *   "uuid" = "uuid",
 * },
 * links = {
 *   "canonical" = "/spectra_data/{spectra_data}",
 *   "edit-form" = "/spectra_data/{spectra_data}/edit",
 *   "delete-form" = "/spectra_data/{spectra_data}/delete",
 *   "collection" = "/spectra_data/list"
 * },
 * field_ui_base_route = "spectra.spectra_data_settings",
 * )
 */
class SpectraData extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSpectraEntityType($type = 'machine_name') {
    switch ($type) {
      case 'class_name':
        return 'SpectraData';
        break;
      case 'short':
        return 'data';
        break;
      case 'machine_name':
      default:
        return 'spectra_data';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['data_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the SpectraData entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Contact entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['data_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Data Type'))
      ->setDescription(t('Used for determining the correct plugin to call, and for indexing.'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ));
    $fields['statement_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Context'))
      ->setDescription(t('The Spectra Statement, which this data should be associated with.'))
      ->setSetting('target_type', 'spectra_statement')
      ->setSetting('handler', 'default');
    $fields['data_data'] = BaseFieldDefinition::create('jsonb')
      ->setLabel(t('JSON Data'))
      ->setDescription(t('The stored JSON data for this data object.'));

    return $fields;
  }

}