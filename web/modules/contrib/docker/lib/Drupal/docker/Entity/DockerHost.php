<?php

/**
 * @file
 * Definition of Drupal\docker\Entity\Docker.
 */

namespace Drupal\docker\Entity;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRenderController;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the file entity class.
 *
 * @EntityType(
 *   id = "docker_host",
 *   label = @Translation("Docker host"),
 *   module = "docker",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageControllerNG",
 *     "access" = "Drupal\docker\DockerHostAccessController",
 *     "list" = "Drupal\docker\DockerHostListController",
 *     "render" = "Drupal\Core\Entity\EntityRenderController",
 *     "form" = {
 *       "default" = "Drupal\docker\DockerHostFormController",
 *       "delete" = "Drupal\docker\Form\DockerHostDeleteForm"
 *     }
 *   },
 *   base_table = "docker_host",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "dhid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/docker/hosts/{docker_host}",
 *     "edit-form" = "/docker/hosts/{docker_host}/edit"
 *   }
 * )
 */
class DockerHost extends EntityNG implements ContentEntityInterface {

  /**
   * The docker host ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $dhid;

  /**
   * The docker host UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uuid;

  /**
   * The docker host user ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uid;

  /**
   * The docker host hostname/ip.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $host;

  /**
   * The docker host port.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $port;

  /**
   * The docker host name.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $name;

  /**
   * The time that the docker host was created.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $created;

  /**
   * The time that the docker host was changed.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $changed;

  /**
   * A boolean field indicating whether the docker host is active.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $status;

  /**
   * Initialize the object. Invoked upon construction and wake up.
   */
  protected function init() {
    parent::init();
    // We unset all defined properties, so magic getters apply.
    unset($this->dhid);
    unset($this->uuid);
    unset($this->uid);
    unset($this->host);
    unset($this->port);
    unset($this->name);
    unset($this->created);
    unset($this->changed);
    unset($this->status);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityInterface::uri().
   */
  public function uri() {
    return array(
      'path' => 'docker/hosts/' . $this->id(),
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
    return $this->get('dhid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['dhid'] = array(
      'label' => t('ID'),
      'description' => t('The docker host ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The docker host UUID.'),
      'type' => 'uuid_field',
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID of the docker uid.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
        'default_value' => 0,
      ),
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t('A unique name for this docker host, not to be confused with the hostname.'),
      'type' => 'string_field',
    );
    $properties['host'] = array(
      'label' => t('Host or IP'),
      'description' => t('The docker hostname or ip.'),
      'type' => 'string_field',
    );
    $properties['port'] = array(
      'label' => t('Port'),
      'description' => t('The docker host port.'),
      'type' => 'integer_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the docker host was created.'),
      'type' => 'integer_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the docker host was changed.'),
      'type' => 'integer_field',
    );
    $properties['status'] = array(
      'label' => t('Active status'),
      'description' => t('A boolean indicating whether the docker host is active.'),
      'type' => 'boolean_field',
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
   * Builds the string used for json requests.
   *
   * @return string Base uri for rest requests to the host.
   */
  public function getEndpoint() {
    return 'http://' . $this->host->value . ':' . $this->port->value;
  }
}