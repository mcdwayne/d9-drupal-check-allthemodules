<?php

namespace Drupal\content_synchronizer\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Export entity entity.
 *
 * @ingroup content_synchronizer
 *
 * @ContentEntityType(
 *   id = "export_entity",
 *   label = @Translation("Export entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\content_synchronizer\Entity\ExportEntityViewBuilder",
 *     "list_builder" = "Drupal\content_synchronizer\ExportEntityListBuilder",
 *     "views_data" = "Drupal\content_synchronizer\Entity\ExportEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\content_synchronizer\Form\ExportEntityForm",
 *       "add" = "Drupal\content_synchronizer\Form\ExportEntityForm",
 *       "edit" = "Drupal\content_synchronizer\Form\ExportEntityForm",
 *       "delete" = "Drupal\content_synchronizer\Form\ExportEntityDeleteForm",
 *     },
 *     "access" = "Drupal\content_synchronizer\ExportEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\content_synchronizer\ExportEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "export_entity",
 *   admin_permission = "administer export entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/export_entity/{export_entity}",
 *     "add-form" = "/admin/structure/export_entity/add",
 *     "edit-form" = "/admin/structure/export_entity/{export_entity}/edit",
 *     "delete-form" = "/admin/structure/export_entity/{export_entity}/delete",
 *     "collection" = "/admin/structure/export_entity",
 *   },
 *   field_ui_base_route = "export_entity.settings"
 * )
 */
class ExportEntity extends ContentEntityBase implements ExportEntityInterface {

  use EntityChangedTrait;

  const TABLE_ITEMS = "content_synchronizer_export_items";
  const FIELD_EXPORT_ID = "export_id";
  const FIELD_ENTITY_ID = "entity_id";
  const FIELD_ENTITY_TYPE = "entity_type";

  /**
   * The list of entity to export.
   *
   * @var array
   */
  protected $entitiesList;

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
      ->setDescription(t('The user ID of author of the Export entity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Export entity entity.'))
      ->setSettings([
        'max_length'      => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type'   => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Export entity is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Add entity to the export entities list.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to add.
   */
  public function addEntity(EntityInterface $entity) {
    if (!$this->hasEntity($entity)) {
      $data = [
        self::FIELD_ENTITY_ID   => $entity->id(),
        self::FIELD_ENTITY_TYPE => $entity->getEntityTypeId(),
        self::FIELD_EXPORT_ID   => $this->id(),
      ];

      \Drupal::database()->insert(self::TABLE_ITEMS)
        ->fields(
          array_keys($data),
          $data
        )->execute();
    }
  }

  /**
   * Check if hte passed entity is already in the entities list.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   */
  public function hasEntity(EntityInterface $entity) {
    return array_key_exists($entity->getEntityTypeId() . '_' . $entity->id(), $this->getEntitiesList());
  }

  /**
   * Remove entity from the export entity list.
   */
  public function removeEntity(EntityInterface $entity) {
    \Drupal::database()->delete(self::TABLE_ITEMS)
      ->condition(self::FIELD_EXPORT_ID, $this->id())
      ->condition(self::FIELD_ENTITY_ID, $entity->id())
      ->condition(self::FIELD_ENTITY_TYPE, $entity->getEntityTypeId())
      ->execute();
  }

  /**
   * Return the entities to export list.
   *
   * @return array
   *   The list of entities to export.
   */
  public function getEntitiesList() {
    if (is_null($this->entitiesList)) {
      $result = \Drupal::database()->select(self::TABLE_ITEMS)
        ->fields(self::TABLE_ITEMS, [
          self::FIELD_ENTITY_ID,
          self::FIELD_ENTITY_TYPE,
        ])
        ->condition(self::FIELD_EXPORT_ID, $this->id())
        ->execute();

      $this->entitiesList = [];
      foreach ($result->fetchAll() as $item) {
        $this->entitiesList[$item->{self::FIELD_ENTITY_TYPE} . '_' . $item->{self::FIELD_ENTITY_ID}] =
          \Drupal::entityTypeManager()->getStorage($item->{self::FIELD_ENTITY_TYPE})->load($item->{self::FIELD_ENTITY_ID});
      }
    }
    return $this->entitiesList;
  }

}
