<?php

namespace Drupal\spectra\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Spectra verb entity.
 *
 * @ingroup spectra
 *
 * @ContentEntityType(
 *   id = "spectra_verb",
 *   label = @Translation("Spectra Verb"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\spectra\Entity\Controller\SpectraVerbListBuilder",
 *     "views_data" = "Drupal\spectra\Entity\Views\SpectraVerbViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\spectra\Form\SpectraVerbForm",
 *       "add" = "Drupal\spectra\Form\SpectraVerbForm",
 *       "edit" = "Drupal\spectra\Form\SpectraVerbForm",
 *       "delete" = "Drupal\spectra\Form\SpectraVerbDeleteForm",
 *     },
 *     "access" = "Drupal\spectra\SpectraVerbAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\spectra\SpectraVerbHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "spectra_verb",
 *   admin_permission = "administer spectra verb entities",
 *   entity_keys = {
 *     "id" = "verb_id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/spectra/spectra_verb/{spectra_verb}",
 *     "add-form" = "/admin/structure/spectra/spectra_verb/add",
 *     "edit-form" = "/admin/structure/spectra/spectra_verb/{spectra_verb}/edit",
 *     "delete-form" = "/admin/structure/spectra/spectra_verb/{spectra_verb}/delete",
 *     "collection" = "/admin/structure/spectra/spectra_verb",
 *   },
 *   field_ui_base_route = "spectra_verb.settings"
 * )
 */
class SpectraVerb extends ContentEntityBase implements SpectraVerbInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSpectraEntityType($type = 'machine_name') {
    switch ($type) {
      case 'class_name':
        return 'SpectraVerb';
        break;
      case 'short':
        return 'verb';
        break;
      case 'machine_name':
      default:
        return 'spectra_verb';
    }
  }

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
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Standard field, used as unique if primary index.
    $fields['verb_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the SpectraAction entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the SpectraAction entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Name'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('Used for determining the correct plugin to call, and for indexing.'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Short description of Item'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 1023,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data'] = BaseFieldDefinition::create('jsonb')
      ->setLabel(t('Additional Data'))
      ->setDescription(t('Additional data to associate with this action.'));

    return $fields;
  }

}
