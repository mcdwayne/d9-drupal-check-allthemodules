<?php

namespace Drupal\micro_path\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\micro_path\MicroPathInterface;

/**
 * Defines the MicroPath entity.
 *
 * @ingroup micro_path
 *
 *
 * @ContentEntityType(
 *   id = "micro_path",
 *   label = @Translation("Micro path entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\micro_path\Controller\MicroPathListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\micro_path\Form\MicroPathForm",
 *       "edit" = "Drupal\micro_path\Form\MicroPathForm",
 *       "delete" = "Drupal\micro_path\Form\MicroPathDeleteForm",
 *     },
 *     "access" = "Drupal\micro_path\MicroPathAccessControlHandler",
 *   },
 *   base_table = "micro_path",
 *   admin_permission = "administer micro path",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "site_id" = "site_id",
 *     "language" = "language",
 *     "alias" = "alias",
 *     "source" = "source",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/micro_path/{micro_path}/edit",
 *     "edit-form" = "/admin/config/micro_path/{micro_path}/edit",
 *     "delete-form" = "/admin/config/micro_path/{micro_path}/delete",
 *     "collection" = "/admin/config/micro_path"
 *   }
 * )
 */
class MicroPath extends ContentEntityBase implements MicroPathInterface {

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
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Term entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Domain path entity.'))
      ->setReadOnly(TRUE);

    // Entity reference field, holds the reference to the site entity.
    $fields['site_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Site Id'))
      ->setDescription(t('The associated site.'))
      ->setSetting('target_type', 'site')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => -7,
      ])
      ->setRequired(TRUE);

    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The language of Domain path entity.'))
      ->setRequired(TRUE);

    // Name field for the micro path.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source'))
      ->setDescription(t('The source path of the micro path alias.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setRequired(TRUE);

    // Name field for the micro path.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['alias'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alias'))
      ->setDescription(t('The alias of the micro path entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * Get source for micro_path.
   *
   * @return string
   *   Returns micro path source.
   */
  public function getSource() {
    return $this->get('source')->value;
  }

  /**
   * Get alias for micro_path.
   *
   * @return string
   *   Returns micro path alias.
   */
  public function getAlias() {
    return $this->get('alias')->value;
  }

  /**
   * Get language code for micro_path.
   *
   * @return string
   *   Returns micro path language code.
   */
  public function getLanguageCode() {
    return $this->get('language')->value;
  }

  /**
   * Get micro site id for micro_path.
   *
   * @return string
   *   Returns micro path site id.
   */
  public function getSiteId() {
    return $this->get('site_id')->target_id;
  }

}
