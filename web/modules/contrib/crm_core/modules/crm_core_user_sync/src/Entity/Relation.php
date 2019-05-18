<?php

namespace Drupal\crm_core_user_sync\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\crm_core_contact\IndividualInterface;
use Drupal\crm_core_user_sync\RelationInterface;
use Drupal\user\UserInterface;

/**
 * Defines the relation entity class.
 *
 * @ContentEntityType(
 *   id = "crm_core_user_sync_relation",
 *   label = @Translation("Relation"),
 *   label_collection = @Translation("Relations"),
 *   handlers = {
 *     "view_builder" = "Drupal\crm_core_user_sync\RelationViewBuilder",
 *     "list_builder" = "Drupal\crm_core_user_sync\RelationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\crm_core_user_sync\Form\RelationForm",
 *       "edit" = "Drupal\crm_core_user_sync\Form\RelationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "crm_core_user_sync_relation",
 *   data_table = "crm_core_user_sync_relation_field_data",
 *   admin_permission = "administer crm-core-user-sync",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/crm-core/user-sync/relation/add",
 *     "canonical" = "/admin/config/crm-core/user-sync/relation/{crm_core_user_sync_relation}",
 *     "edit-form" = "/admin/config/crm-core/user-sync/relation/{crm_core_user_sync_relation}/edit",
 *     "delete-form" = "/admin/config/crm-core/user-sync/relation/{crm_core_user_sync_relation}/delete",
 *     "collection" = "/admin/config/crm-core/user-sync/relation"
 *   }
 * )
 */
class Relation extends ContentEntityBase implements RelationInterface {

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndividual() {
    return $this->get('individual_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndividualId() {
    return $this->get('individual_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndividualId($individual_id) {
    $this->set('individual_id', $individual_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndividual(IndividualInterface $individual) {
    $this->set('individual_id', $individual->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setCardinality(1)
      // @todo Replace with what ever would work with entity reference
      // from core. https://www.drupal.org/project/drupal/issues/2973455
      ->addConstraint('UniqueReference')
      ->setLabel(t('User'))
      ->setDescription(t('The user ID of the relation.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['individual_id'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setCardinality(1)
      // @todo Replace with what ever would work with entity reference
      // from core. https://www.drupal.org/project/drupal/issues/2973455
      ->addConstraint('UniqueReference')
      ->setLabel(t('Individual'))
      ->setDescription(t('The individual ID of the relation.'))
      ->setSetting('target_type', 'crm_core_individual')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
