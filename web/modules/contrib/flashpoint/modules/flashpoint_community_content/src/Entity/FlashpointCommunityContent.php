<?php

namespace Drupal\flashpoint_community_content\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Flashpoint community content entity.
 *
 * @ingroup flashpoint_community_content
 *
 * @ContentEntityType(
 *   id = "flashpoint_community_content",
 *   label = @Translation("Flashpoint community content"),
 *   bundle_label = @Translation("Flashpoint community content type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\flashpoint_community_content\FlashpointCommunityContentListBuilder",
 *     "views_data" = "Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentViewsData",
 *     "translation" = "Drupal\flashpoint_community_content\FlashpointCommunityContentTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\flashpoint_community_content\Form\FlashpointCommunityContentForm",
 *       "add" = "Drupal\flashpoint_community_content\Form\FlashpointCommunityContentForm",
 *       "edit" = "Drupal\flashpoint_community_content\Form\FlashpointCommunityContentForm",
 *       "delete" = "Drupal\flashpoint_community_content\Form\FlashpointCommunityContentDeleteForm",
 *     },
 *     "access" = "Drupal\flashpoint_community_content\FlashpointCommunityContentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\flashpoint_community_content\FlashpointCommunityContentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "flashpoint_community_content",
 *   data_table = "flashpoint_community_content_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer flashpoint community content entities",
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
 *     "canonical" = "/flashpoint_community_content/{flashpoint_community_content}",
 *     "add-page" = "/flashpoint_community_content/add",
 *     "add-form" = "/flashpoint_community_content/add/{flashpoint_community_c_type}",
 *     "edit-form" = "/flashpoint_community_content/{flashpoint_community_content}/edit",
 *     "delete-form" = "/flashpoint_community_content/{flashpoint_community_content}/delete",
 *     "collection" = "/admin/content/flashpoint/flashpoint_community_content",
 *   },
 *   bundle_entity_type = "flashpoint_community_c_type",
 *   field_ui_base_route = "entity.flashpoint_community_c_type.edit_form"
 * )
 */
class FlashpointCommunityContent extends ContentEntityBase implements FlashpointCommunityContentInterface {

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
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Flashpoint community content entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Flashpoint community content entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Flashpoint community content is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
