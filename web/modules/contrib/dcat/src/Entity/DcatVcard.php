<?php

namespace Drupal\dcat\Entity;

use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the vCard entity.
 *
 * @ingroup dcat
 *
 * @ContentEntityType(
 *   id = "dcat_vcard",
 *   label = @Translation("vCard"),
 *   label_singular = @Translation("vCard"),
 *   label_plural = @Translation("vCards"),
 *   label_count = @PluralTranslation(
 *     singular = "@count vCard",
 *     plural = "@count vCards",
 *   ),
 *   bundle_label = @Translation("vCard type"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dcat\DcatVcardListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dcat\Form\DcatVcardForm",
 *       "add" = "Drupal\dcat\Form\DcatVcardForm",
 *       "edit" = "Drupal\dcat\Form\DcatVcardForm",
 *       "delete" = "Drupal\dcat\Form\DcatVcardDeleteForm",
 *     },
 *     "access" = "Drupal\dcat\DcatVcardAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dcat\DcatVcardHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dcat_vcard",
 *   data_table = "dcat_vcard_field_data",
 *   admin_permission = "administer vcard entities",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/dcat_vcard/{dcat_vcard}",
 *     "add-page" = "/admin/structure/dcat/dcat_vcard/add",
 *     "add-form" = "/admin/structure/dcat/dcat_vcard/add/{dcat_vcard_type}",
 *     "edit-form" = "/dcat_vcard/{dcat_vcard}/edit",
 *     "delete-form" = "/dcat_vcard/{dcat_vcard}/delete",
 *     "collection" = "/admin/structure/dcat/dcat_vcard",
 *   },
 *   bundle_entity_type = "dcat_vcard_type",
 *   field_ui_base_route = "entity.dcat_vcard_type.edit_form"
 * )
 */
class DcatVcard extends ContentEntityBase implements DcatVcardInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the vCard entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['external_id'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('vCard IRI'))
      ->setDescription(t('The (external) vCard IRI.'))
      ->setSettings(array(
        'max_length' => 1020,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'uri_link',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Formatted name / title.'))
      ->setSettings(array(
        'max_length' => 1020,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the vCard is published.'))
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
