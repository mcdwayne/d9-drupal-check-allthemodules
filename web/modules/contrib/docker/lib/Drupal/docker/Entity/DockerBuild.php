<?php

/**
 * @file
 * Definition of Drupal\docker\Entity\Docker.
 */

namespace Drupal\docker\Entity;

use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityRenderController;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the file entity class.
 *
 * @EntityType(
 *   id = "docker_build",
 *   label = @Translation("Docker build"),
 *   module = "docker",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageController",
 *     "access" = "Drupal\docker\DockerBuildAccessController",
 *     "list" = "Drupal\docker\DockerBuildListController",
 *     "render" = "Drupal\Core\Entity\EntityRenderController",
 *     "form" = {
 *       "add" = "Drupal\docker\DockerBuildAddFormController",
 *       "edit" = "Drupal\docker\DockerBuildFormController",
 *       "delete" = "Drupal\docker\DockerBuildDeleteForm"
 *     }
 *   },
 *   base_table = "docker_build",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "dbid",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "machine_name" = "machine_name"
 *   }
 * )
 */
//class DockerBuild extends ContentEntityBase implements ContentEntityInterface {
class DockerBuild extends ContentEntityBase implements ContentEntityInterface {

  /**
   * The docker build ID.
   *
   * @var int
   */
  public $dbid;

  /**
   * The docker build UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The docker build machine name.
   *
   * @var string
   */
  public $machine_name;

  /**
   * The docker build label.
   *
   * @var string
   */
  public $label;

  /**
   * The docker build description.
   *
   * @var string
   */
  public $description;

  /**
   * The time that the docker build was created.
   *
   * @var int
   */
  public $created;

  /**
   * The time that the docker build was changed.
   *
   * @var int
   */
  public $changed;

  /**
   * The comment language code.
   *
   * @var string
   */
  public $langcode;


  /**
   * Overrides Drupal\Core\Entity\EntityInterface::uri().
   */
  public function uri() {
    return array(
      'path' => 'docker/builds/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->dbid;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['dbid'] = array(
      'label' => t('ID'),
      'description' => t('The docker build ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The docker build UUID.'),
      'type' => 'uuid_field',
    );
    $properties['machine_name'] = array(
      'label' => t('Machine Name'),
      'description' => t('The docker build machine name.'),
      'type' => 'string_field',
    );
    $properties['label'] = array(
      'label' => t('Name'),
      'description' => t('The docker build name.'),
      'type' => 'string_field',
    );
    $properties['description'] = array(
      'label' => t('Description'),
      'description' => t('The docker build description.'),
      'type' => 'string_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the docker build was created.'),
      'type' => 'integer_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the docker build was changed.'),
      'type' => 'integer_field',
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The comment language code.'),
      'type' => 'language_field',
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionId() {
    return NULL;
  }

}