<?php

namespace Drupal\badge\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Badge awarded entity.
 *
 * @ingroup badge
 *
 * @ContentEntityType(
 *   id = "badge_awarded",
 *   label = @Translation("Badge awarded"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\badge\BadgeAwardedListBuilder",
 *     "views_data" = "Drupal\badge\Entity\BadgeAwardedViewsData",
 *     "translation" = "Drupal\badge\BadgeAwardedTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\badge\Form\BadgeAwardedForm",
 *       "add" = "Drupal\badge\Form\BadgeAwardedForm",
 *       "edit" = "Drupal\badge\Form\BadgeAwardedForm",
 *       "delete" = "Drupal\badge\Form\BadgeAwardedDeleteForm",
 *     },
 *     "access" = "Drupal\badge\BadgeAwardedAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\badge\BadgeAwardedHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "badge_awarded",
 *   data_table = "badge_awarded_field_data",
 *   translatable = TRUE,
  *   admin_permission = "administer badge awarded entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/badge/badge_awarded/{badge_awarded}",
 *     "add-form" = "/admin/structure/badge/badge_awarded/add",
 *     "edit-form" = "/admin/structure/badge/badge_awarded/{badge_awarded}/edit",
 *     "delete-form" = "/admin/structure/badge/badge_awarded/{badge_awarded}/delete",
 *     "collection" = "/admin/structure/badge/badge_awarded",
 *   },
 *   field_ui_base_route = "entity.badge_awarded.collection"
 * )
 */
class BadgeAwarded extends ContentEntityBase implements BadgeAwardedInterface {

  use EntityChangedTrait;

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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Entity'))
      ->setDescription(t('The entity.'))
      ->setSettings(array(
        'user' => array(
          'handler' => 'default:user',
          'handler_settings' => array(
            'include_anonymous' => FALSE,
          ),
        ),
        'badge' => array(
          'handler' => 'default:badge',
          'hanlder_settings' => array(),
        ),
        'badge_awarded' => array(
          'handler' => 'badge_awarded',
          'hanlder_settings' => array(),
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'dynamic_entity_reference_default',
        'weight' => 100,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '40',
          'placeholder' => '',
        ),
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['badge_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('The badge awarded'))
      ->setDescription(t('The badge awarded to this entity.'))
      ->setSetting('target_type', 'badge')
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
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Badge awarded is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', array(
        'type' => 'timestamp',
        'label' => 'above',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
