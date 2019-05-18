<?php

namespace Drupal\copyscape\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Copyscape result entity.
 *
 * @ingroup copyscape
 *
 * @ContentEntityType(
 *   id = "copyscape_result",
 *   label = @Translation("Copyscape result"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *
 *     "form" = {
 *       "delete" = "Drupal\copyscape\Form\CopyscapeDeleteForm",
 *     },
 *     "access" = "Drupal\copyscape\Access\CopyscapeAccessControlHandler",
 *   },
 *   base_table = "copyscape_result",
 *   admin_permission = "administer copyscape entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "name" = "name",
 *     "nid" = "nid",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "response" = "response",
 *   },
 *   links = {
 *     "delete-form" = "/copyscape/{copyscape_result/delete",
 *     "results" = "/copyscape/results",
 *   },
 * )
 */
class CopyscapeResult extends ContentEntityBase implements CopyscapeResultInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
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
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Copyscape result entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['nid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Node id'))
      ->setDescription(t('The node id.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of node being successfully created.'));

    $fields['response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Response'))
      ->setDescription(t('The response body from copyscape.'));

    return $fields;
  }

}
