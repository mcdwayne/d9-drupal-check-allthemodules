<?php

/**
 * @file
 * Contains \Drupal\collect\CollectStorage.
 */

namespace Drupal\collect;

use Drupal\collect\Entity\Container;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the controller class for collect containers.
 *
 * This extends the base storage class, adding required special handling for
 * collect container entities.
 */
class CollectStorage extends SqlContentEntityStorage implements CollectStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(CollectContainerInterface $collect_container) {
    return $this->database->query(
      'SELECT vid FROM {collect_revision} WHERE id=:id ORDER BY vid',
      array(':id' => $collect_container->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getIdsByUriPatterns(array $uri_patterns, $limit = NULL, $offset = NULL) {
    $query = $this->getQuery('OR');
    foreach ($uri_patterns as $uri_pattern) {
      $query->condition('schema_uri', $uri_pattern, 'STARTS_WITH');
    }
    if ($limit) {
      $query->range($offset, $limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadByOriginUri($origin_uri) {
    $id = $this->getQuery()
      ->condition('origin_uri', $origin_uri)
      ->sort('date', 'DESC')
      ->range(0, 1)
      ->execute();
    return $id ? $this->load(reset($id)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function persist(CollectContainerInterface $container, $is_container_revision = FALSE) {
    $ids = $this->getQuery()
      ->condition('origin_uri', $container->getOriginUri())
      ->condition('schema_uri', $container->getSchemaUri())
      ->sort('id', 'DESC')
      ->pager(1)
      ->execute();

    if (empty($ids) || !$is_container_revision) {
      // Save new container.
      $container->save();
    }
    else {
      // Save the new container as a new revision of the existing container,
      // unless the containers are equivalent.
      /** @var \Drupal\collect\CollectContainerInterface $existing_container */
      $existing_container = $this->load(current($ids));
      // @todo ask the plugin to compare.
      if ($existing_container->getData() != $container->getData()) {
        $existing_container->setData($container->getData());
        $existing_container->setDate($container->getDate());
        $existing_container->setSchemaUri($container->getSchemaUri());
        $existing_container->setType($container->getType());
        $existing_container->setNewRevision();
        $existing_container->save();
      }
      $container = $existing_container;
    }

    return $container;
  }

}
