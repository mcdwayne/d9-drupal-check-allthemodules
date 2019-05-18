<?php

namespace Drupal\media_reference_revisions\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\media_entity\Entity\Media;

/**
 * Defines the Media Reference Revision entity.
 *
 * @ingroup media_reference_revisions
 *
 * @ContentEntityType(
 *   id = "media_reference_revision",
 *   label = @Translation("Media reference revision"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\media_reference_revisions\MediaReferenceRevisionListBuilder",
 *     "views_data" = "Drupal\media_reference_revisions\Entity\MediaReferenceRevisionViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\media_reference_revisions\Form\MediaReferenceRevisionForm",
 *       "add" = "Drupal\media_reference_revisions\Form\MediaReferenceRevisionForm",
 *       "edit" = "Drupal\media_reference_revisions\Form\MediaReferenceRevisionForm",
 *       "delete" = "Drupal\media_reference_revisions\Form\MediaReferenceRevisionDeleteForm",
 *     },
 *     "access" = "Drupal\media_reference_revisions\MediaReferenceRevisionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\media_reference_revisions\MediaReferenceRevisionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "media_reference_revision",
 *   admin_permission = "administer media reference revision entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "entity_type" = "entity_type",
 *     "entity_id" = "entity_id",
 *     "entity_vid" = "entity_vid",
 *     "media_id" = "media_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/media_reference_revision/{media_reference_revision}",
 *     "add-form" = "/admin/structure/media_reference_revision/add",
 *     "edit-form" = "/admin/structure/media_reference_revision/{media_reference_revision}/edit",
 *     "delete-form" = "/admin/structure/media_reference_revision/{media_reference_revision}/delete",
 *     "collection" = "/admin/structure/media_reference_revision",
 *   },
 *   field_ui_base_route = "media_reference_revision.settings"
 * )
 */
class MediaReferenceRevision extends ContentEntityBase {

  /**
   * Load the specific revision for a media item based upon the entity it's on.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity that the media item is attached to.
   * @param int $media_id
   *   The ID of the media item that is attached to the entity.
   *
   * @return \Drupal\media_entity\Entity\Media|null
   *   Either a media entity or NULL.
   */
  public static function loadMediaEntity(EntityInterface $entity, $media_id) {
    /** @var \Drupal\Core\Database\Database $database */
    $database = \Drupal::database();

    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $database->select('media_reference_revision', 'mrr')
      ->fields('mrr', ['media_vid'])
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('entity_vid', $entity->getRevisionId())
      ->condition('media_id', $media_id);

    $query->orderBy('mrr.media_vid', 'DESC');

    $media_vid = $query->execute()->fetchColumn();

    if (!empty($media_vid)) {
      return \Drupal::entityTypeManager()
        ->getStorage('media')
        ->loadRevision($media_vid);
    }

    return NULL;
  }

  public static function loadLatestMediaRevision(Media $media) {
    $vid = self::getLatestMediaRevisionId($media);

    if (!empty($vid)) {
      return \Drupal::entityTypeManager()
        ->getStorage('media')
        ->loadRevision($vid);
    }
  }

  public static function getLatestMediaRevisionId(Media $media) {
    /** @var \Drupal\Core\Database\Database $database */
    $database = \Drupal::database();

    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $database->select('media_revision', 'mr')
      ->fields('mr', ['vid'])
      ->condition('mr.mid', $media->id())
      ->orderBy('mr.vid', 'DESC')
      ->range(0, 1);

    return $query->execute()->fetchColumn();
  }

  public static function loadLatestNodeRevision(EntityInterface $node) {
    $vid = self::getLatestNodeRevisionId($node);

    if (!empty($vid) && $vid != $node->getRevisionId()) {
      return \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadRevision($vid);
    }

    // If nothing found, just return the current node
    return $node;
  }

  public static function getLatestNodeRevisionId(EntityInterface $node) {
    /** @var \Drupal\Core\Database\Database $database */
    $database = \Drupal::database();

    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $database->select('node_revision', 'nr')
      ->fields('nr', ['vid'])
      ->condition('nr.nid', $node->id())
      ->orderBy('nr.vid', 'DESC')
      ->range(0, 1);

    return $query->execute()->fetchColumn();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setRequired(TRUE)
      ->setReadOnly(FALSE)
        ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent entity'))
      ->setRevisionable(FALSE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Node Revision ID'))
      ->setRequired(TRUE)
      ->setReadOnly(FALSE)
      ->setSetting('unsigned', TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['media_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Media Entity'))
      ->setRevisionable(FALSE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'media')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['media_vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setRequired(TRUE)
      ->setReadOnly(FALSE)
      ->setSetting('unsigned', TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
