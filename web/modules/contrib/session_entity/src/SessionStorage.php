<?php

namespace Drupal\session_entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\SchemaException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\ContentEntityNullStorage;
use Drupal\Core\Entity\EntityBundleListenerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 */
class SessionStorage extends ContentEntityNullStorage {

  /**
   * Constructs a SessionStorage object.
   */
  public function __construct(EntityTypeInterface $entity_type,
      EntityManagerInterface $entity_manager,
      CacheBackendInterface $cache,
      PrivateTempStoreFactory $temp_store_factory,
      SessionManagerInterface $session_manager,
      AccountInterface $current_user) {
    parent::__construct($entity_type, $entity_manager, $cache);

    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    // Load the entity from the user's private tempstore.
    $per_user_private_temp_store = $this->tempStoreFactory->get('session_entity');
    $entity = $per_user_private_temp_store->get('session_entity');
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    if ($this->currentUser->isAnonymous()) {
      // Force a session to be started for anonymous users. Workaround for a
      // core bug.
      // TODO: remove this when https://www.drupal.org/node/2743931 is fixed.
      $_SESSION['session_entity_forced'] = '';
    }

    // Save the entity to the user's private tempstore.
    $per_user_private_temp_store = $this->tempStoreFactory->get('session_entity');
    $per_user_private_temp_store->set('session_entity', $entity);
  }

}
