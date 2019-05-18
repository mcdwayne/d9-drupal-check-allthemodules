<?php


namespace Drupal\config_entity_revisions;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

trait ConfigEntityRevisionsConfigTrait {

  /**
   * @var int
   *   The ID of the revision that was loaded.
   */
  public $loadedRevisionId;


  /**
   * Restore the entity type manager after deserialisation.
   */
  public function __wakeup() {
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * Gets the revision identifier of the entity.
   *
   * @return int
   *   The revision identifier of the entity, or NULL if the entity does not
   *   have a revision identifier.
   */
  public function getRevisionId() {
    return $this->loadedRevisionId;
  }

  /**
   * Set revision ID.
   *
   * @param int $revisionID
   *   The revision ID that this class instance represents.
   */
  public function updateLoadedRevisionId($revisionID) {
    $this->loadedRevisionId = $revisionID;
  }

  /**
   * Get the config entity storage.
   *
   * @return ConfigEntityStorageInterface
   *   The storage for the config entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function configEntityStorage() {
    return $this->entityTypeManager->getStorage('config_entity_revisions');
  }

  /**
   * Get the revisions entity storage.
   *
   * @return ContentEntityStorageInterface
   *   The storage for the revisions entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function contentEntityStorage() {
    return $this->entityTypeManager->getStorage('config_entity_revisions');
  }

  /**
   * Default revision of revisions entity that matches the config entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The matching entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getContentEntity() {
    $contentEntityID = $this->getContentEntityID();

    if (!$contentEntityID) {
      return NULL;
    }

    /* @var $storage \Drupal\Core\Entity\ContentEntityStorageInterface */
    $storage = $this->contentEntityStorage();

    // Get the matching revision ID if one is provided.
    if ($this->getRevisionId()) {
      return $storage->loadRevision($this->getRevisionId());
    }

    // Otherwise, just get the default revision.
    return $storage->load($contentEntityID);
  }

}