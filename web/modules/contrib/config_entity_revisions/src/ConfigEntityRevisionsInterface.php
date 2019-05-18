<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * ConfigEntityRevisionsInterface.
 *
 * Adds revision related fields to a configuration entity.
 */

interface ConfigEntityRevisionsInterface extends ConfigEntityInterface {

  /**
   * @return string
   *   The name of the module implementing the API.
   */
  public function module_name();

  /**
   * @return string
   *   The name of the entity being revisioned.
   */
  public function config_entity_name();

  /**
   * @return string
   *   The name of the content entity in which revisions are being stored.
   */
  public function revisions_entity_name();

  /**
   * @return string
   *   The name of the setting on the config entity in which content entity
   *   ids are stored.
   */
  public function setting_name();

  /**
   * @return string
   *   The proper name (displayed to the user) of the module implementing the
   *   API.
   */
  public function title();

  /**
   * @return boolean
   *   Does the config entity have its own content entities?
   */
  public function has_own_content();

  /**
   * @return string
   *   The name of the content entities that the config entity has.
   */
  public function content_entity_type();

  /**
   * @return string
   *   @TODO.
   */
  public function content_parameter_name();

  /**
   * @return string
   *   @TODO.
   */
  public function content_parent_reference_field();

  /**
   * @return string
   *   The name of the module implementing the API.
   */
  public function admin_permission();
  /**
   * Get the config entity storage.
   *
   * @return ConfigEntityStorageInterface
   *   The storage for the config entity.
   */
  public function configEntityStorage();

  /**
   * Get the revisions entity storage.
   *
   * @return ContentEntityStorageInterface
   *   The storage for the revisions entity.
   */
  public function contentEntityStorage();

  /**
   * Set in the configEntity an identifier for the matching content entity.
   *
   * @param mixed $contentEntityID
   *   The ID used to match the content entity.
   */
  public function setContentEntityID($contentEntityID);

  /**
   * Get from the configEntity the ID of the matching content entity.
   *
   * @return int|null
   *   The ID (if any) of the matching content entity.
   */
  public function getContentEntityID();

  /**
   * Default revision of revisions entity that matches the config entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The matching entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getContentEntity();

  /**
   * Gets the revision identifier of the entity.
   *
   * @return int
   *   The revision identifier of the entity, or NULL if the entity does not
   *   have a revision identifier.
   */
  public function getRevisionId();

  /**
   * Set revision ID.
   *
   * @param int $revisionID
   *   The revision ID that this class instance represents.
   */
  public function updateLoadedRevisionId($revisionID);

}