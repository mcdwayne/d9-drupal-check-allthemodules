<?php
/**
 * @file
 * Contains \Drupal\collect\Entity\Relation.
 */

namespace Drupal\collect\Entity;

use Drupal\collect\Relation\RelationInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Entity type describing a relation between two resources.
 *
 * @todo Prohibit multiple relations with same source+target+relation.
 *
 * @ContentEntityType(
 *   id = "collect_relation",
 *   label = @Translation("Relation"),
 *   admin_permission = "administer collect",
 *   base_table = "collect_relation",
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\collect\Form\RelationForm",
 *       "delete" = "Drupal\collect\Form\RelationDeleteForm"
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/collect/relation",
 *     "canonical" = "/admin/content/collect/relation/{collect_relation}",
 *     "add-form" = "/admin/content/collect/relation/add",
 *     "edit-form" = "/admin/content/collect/relation/{collect_relation}/edit",
 *     "delete-form" = "/admin/content/collect/relation/{collect_relation}/delete",
 *   }
 * )
 */
class Relation extends ContentEntityBase implements RelationInterface {

  /**
   * {@inheritdoc}
   */
  public function getSourceUri() {
    return $this->source_uri->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceUri($source_uri) {
    $this->source_uri = $source_uri;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetUri() {
    return $this->target_uri->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetUri($target_uri) {
    $this->target_uri = $target_uri;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceId() {
    return $this->source_id->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceId($source_id) {
    $this->source_id = $source_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetId() {
    return $this->target_id->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetId($target_id) {
    $this->target_id = $target_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationUri() {
    return $this->relation_uri->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRelationUri($relation_uri) {
    $this->relation_uri = $relation_uri;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getSourceId()) {
      $this->setSourceId($this->getContainerIdByUri($this->getSourceUri()));
    }
    if (!$this->getTargetId()) {
      $this->setTargetId($this->getContainerIdByUri($this->getTargetUri()));
    }
  }

  /**
   * Finds the latest container with the given origin URI.
   *
   * @param string $origin_uri
   *   Needle URI.
   *
   * @return int
   *   The ID of the latest container with the given origin URI.
   */
  protected function getContainerIdByUri($origin_uri) {
    return $this->entityManager()->getStorage('collect_container')->getQuery()
      ->condition('origin_uri', $origin_uri)
      ->range(0, 1)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $violations = parent::validate();

    // For the source and target, if there is a container reference, the URI set must be
    $new_constraint_violation = function($template, $params, $property) {
      return new ConstraintViolation(t($template, $params), $template, $params, $this, $property . '.0', $this->get($property)->first());
    };

    if ($this->getSourceId()) {
      $source_container = $this->entityManager()->getStorage('collect_container')->load($this->getSourceId());
      if ($source_container && $source_container->getOriginUri() != $this->getSourceUri()) {
        $violations->add($new_constraint_violation('Source URI %source_uri is different from origin URI of source container %container_uri', ['%source_uri' => $this->getSourceUri(), '%container_uri' => $source_container->getOriginUri()], 'source_uri'));
      }
    }

    if ($this->getTargetId()) {
      $target_container = $this->entityManager()->getStorage('collect_container')->load($this->getTargetId());
      if ($target_container && $target_container->getOriginUri() != $this->getTargetUri()) {
        $violations->add($new_constraint_violation('Target URI %target_uri is different from origin URI of target container %container_uri', ['%target_uri' => $this->getTargetUri(), '%container_uri' => $target_container->getOriginUri()], 'target_uri'));
      }
    }

    return $violations;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Relation ID'))
      ->setDescription(t('Internal ID of the relation.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'hidden',
        'weight' => -10,
      ]);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    $fields['source_uri'] = BaseFieldDefinition::create('string')
      ->setLabel('Source URI')
      ->setDescription('The URI of the source entity (the relation "subject").')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 10,
      ]);

    $fields['source_id'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'collect_container')
      ->setLabel('Source container')
      ->setDescription('The source data container entity. Can be empty if the source is not collected as a container.');

    $fields['target_uri'] = BaseFieldDefinition::create('string')
      ->setLabel('Target URI')
      ->setDescription('The URI of the target entity (the relation "object").')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 20,
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 20,
      ]);

    $fields['target_id'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'collect_container')
      ->setLabel('Target container')
      ->setDescription('The target data container entity. Can be empty if the target is not collected as a container.');

    $fields['relation_uri'] = BaseFieldDefinition::create('string')
      ->setLabel('Relation URI')
      ->setDescription('The URI of the relation (the "predicate").')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 30,
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 30,
      ]);

    return $fields;
  }

}
