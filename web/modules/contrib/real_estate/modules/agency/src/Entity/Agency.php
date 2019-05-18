<?php

namespace Drupal\real_estate_agency\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Agency entity.
 *
 * @ingroup real_estate_agency
 *
 * @ContentEntityType(
 *   id = "real_estate_agency",
 *   label = @Translation("Agency"),
 *   bundle_label = @Translation("Agency type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\real_estate_agency\AgencyListBuilder",
 *     "views_data" = "Drupal\real_estate_agency\Entity\AgencyViewsData",
 *     "translation" = "Drupal\real_estate_agency\AgencyTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\real_estate_agency\Form\AgencyForm",
 *       "add" = "Drupal\real_estate_agency\Form\AgencyForm",
 *       "edit" = "Drupal\real_estate_agency\Form\AgencyForm",
 *       "delete" = "Drupal\real_estate_agency\Form\AgencyDeleteForm",
 *     },
 *     "access" = "Drupal\real_estate_agency\AgencyAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\real_estate_agency\AgencyHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "real_estate_agency",
 *   data_table = "real_estate_agency_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer agency entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/agency/{real_estate_agency}",
 *     "add-page" = "/agency/add",
 *     "add-form" = "/agency/add/{real_estate_agency_type}",
 *     "edit-form" = "/agency/{real_estate_agency}/edit",
 *     "delete-form" = "/agency/{real_estate_agency}/delete",
 *     "collection" = "/admin/real-estate/config/agencies",
 *   },
 *   bundle_entity_type = "real_estate_agency_type",
 *   field_ui_base_route = "entity.real_estate_agency_type.edit_form"
 * )
 */
class Agency extends ContentEntityBase implements AgencyInterface {

  use EntityChangedTrait;

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
  public function getType() {
    return $this->bundle();
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

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owned by'))
      ->setDescription(t('The user ID of owner of the Agency.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Agency.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Agency is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
