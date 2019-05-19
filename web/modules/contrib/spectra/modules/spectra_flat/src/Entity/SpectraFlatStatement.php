<?php
/**
 * @file
 * Contains \Drupal\spectra_flat\Entity\SpectraFlatStatement.
 */

namespace Drupal\spectra_flat\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the SpectraFlatStatement entity.
 *
 * @ingroup spectra_flat
 *
 *
 * @ContentEntityType(
 * id = "spectra_flat_statement",
 * label = @Translation("Spectra Flat Statement"),
 * handlers = {
 *   "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   "list_builder" = "Drupal\spectra_flat\Entity\Controller\SpectraFlatStatementListBuilder",
 *   "views_data" = "Drupal\spectra_flat\Entity\Views\SpectraFlatStatementViewsData",
 *   "form" = {
 *     "add" = "Drupal\spectra_flat\Form\SpectraFlatStatementForm",
 *     "edit" = "Drupal\spectra_flat\Form\SpectraFlatStatementForm",
 *     "delete" = "Drupal\spectra_flat\Form\SpectraFlatStatementDeleteForm",
 *   },
 *   "access" = "Drupal\spectra_flat\SpectraFlatStatementAccessControlHandler",
 * },
 * base_table = "spectra_flat_statement",
 * admin_permission = "administer spectra_flat_statement entity",
 * fieldable = TRUE,
 * entity_keys = {
 *   "id" = "flat_statement_id",
 *   "uuid" = "uuid",
 * },
 * links = {
 *   "canonical" = "/spectra_flat_statement/{spectra_flat_statement}",
 *   "edit-form" = "/spectra_flat_statement/{spectra_flat_statement}/edit",
 *   "delete-form" = "/spectra_flat_statement/{spectra_flat_statement}/delete",
 *   "collection" = "/spectra_flat_statement/list"
 * },
 * field_ui_base_route = "spectra_flat.spectra_flat_statement_settings",
 * )
 */
class SpectraFlatStatement extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSpectraEntityType($type = 'machine_name') {
    switch ($type) {
      case 'class_name':
        return 'SpectraFlatStatement';
        break;
      case 'short':
        return 'flat_statement';
        break;
      case 'machine_name':
      default:
        return 'spectra_flat_statement';
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
    $fields['flat_statement_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the SpectraFlatStatement entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the SpectraFlatStatement entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['flat_statement_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Statement Time'))
      ->setDescription(t('The time of the statement event.'));

    $fields['flat_statement_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Statement Type'))
      ->setDescription(t('Used for determining the correct plugin to call, and for indexing.'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['flat_statement_type_secondary'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Secondary Statement Type'))
      ->setDescription(t('Used for determining the correct plugin to call, and for indexing.'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['parent_flat_statement'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent Statement'))
      ->setDescription(t('The Parent statement, to which this item is a sub-statement'))
      ->setSetting('target_type', 'spectra_flat_statement')
      ->setSetting('handler', 'default');

    $fields['flat_statement_data'] = BaseFieldDefinition::create('jsonb')
      ->setLabel(t('Statement JSON Data'))
      ->setDescription(t('The stored JSON data for this data object.'));

    return $fields;
  }

}