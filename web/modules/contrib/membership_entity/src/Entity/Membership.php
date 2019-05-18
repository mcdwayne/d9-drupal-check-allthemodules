<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Membership entity.
 *
 * @ingroup membership_entity
 *
 * @ContentEntityType(
 *   id = "membership_entity",
 *   label = @Translation("Membership"),
 *   bundle_label = @Translation("Membership type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\membership_entity\Entity\MembershipListBuilder",
 *     "views_data" = "Drupal\membership_entity\Entity\MembershipViewsData",
 *     "form" = {
 *       "default" = "Drupal\membership_entity\Form\MembershipForm",
 *       "add" = "Drupal\membership_entity\Form\MembershipForm",
 *       "edit" = "Drupal\membership_entity\Form\MembershipForm",
 *       "delete" = "Drupal\membership_entity\Form\MembershipDeleteForm",
 *     },
 *     "access" = "Drupal\membership_entity\Entity\MembershipAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\membership_entity\Entity\MembershipHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "membership_entity",
 *   data_table = "membership_entity_field_data",
 *   translatable = FALSE,
 *   admin_permission = "administer membership entities",
 *   entity_keys = {
 *     "id" = "member_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/memberships/reports/list",
 *     "add-page" = "/admin/memberships/add",
 *     "add-form" = "/admin/memberships/add/{membership_entity_type}",
 *     "canonical" = "/membership/{membership_entity}",
 *     "edit-form" = "/membership/{membership_entity}/edit",
 *     "delete-form" = "/membership/{membership_entity}/delete",
  *   },
 *   bundle_entity_type = "membership_entity_type",
 *   field_ui_base_route = "entity.membership_entity_type.edit_form"
 * )
 */
class Membership extends ContentEntityBase implements MembershipInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberID(): string {
    return $this->get('member_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberID(string $member_id): MembershipInterface {
    $this->set('member_id', $member_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): MembershipInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['member_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Member ID'))
      ->setDescription(t('The unique Membership identifier.'))
      ->setSettings([
        // Hard limit is 191 due to MySQL <= 5.6 key size limit of 767 bytes.
        // 50 should be plenty.
        'max_length' => 50,
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
