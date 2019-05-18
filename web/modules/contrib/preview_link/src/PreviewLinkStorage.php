<?php

namespace Drupal\preview_link;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PreviewLinkStorage extends SqlContentEntityStorage implements PreviewLinkStorageInterface {

  /**
   * Constructs a SqlContentEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Component\Uuid\UuidInterface
   *   The uuid generator.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, UuidInterface $uuid_service) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager);
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewLinkForEntity(ContentEntityInterface $entity) {
    return $this->getPreviewLink($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewLink(ContentEntityInterface $entity) {
    $result = $this->loadByProperties([
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);
    return $result ? array_pop($result) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createPreviewLinkForEntity(ContentEntityInterface $entity) {
    return $this->createPreviewLink($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function createPreviewLink($entity_type_id, $entity_id) {
    $preview_link = $this->create([
      'entity_id' => $entity_id,
      'entity_type_id' => $entity_type_id,
    ]);
    $preview_link->save();
    return $preview_link;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = array()) {
    return parent::create($values + [
      'token' => $this->generateUniqueToken(),
      'generated_timestamp' => time(),
    ]);
  }

  public function save(EntityInterface $entity) {
    if ($entity->regenerateToken()) {
      $entity->setToken($this->generateUniqueToken());
    }
    return parent::save($entity);
  }

  /**
   * Gets the unique token for the link.
   *
   * This token is unique every time we generate a link, there is nothing
   * from the original entity involved in the token so it does not need to be
   * cryptographically secure, only sufficiently random which UUID is.
   *
   * @return string
   *   A unique identifier for this preview link.
   */
  protected function generateUniqueToken() {
    return $this->uuidService->generate();
  }

}
