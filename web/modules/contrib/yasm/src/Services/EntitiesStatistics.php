<?php

namespace Drupal\yasm\Services;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements entities statistics class.
 */
class EntitiesStatistics implements EntitiesStatisticsInterface {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function count($entity_id, array $conditions = []) {
    $query = $this->entityTypeManager->getStorage($entity_id)->getQuery();

    if (!empty($conditions)) {
      foreach ($conditions as $key => $value) {
        if (is_array($value)) {
          $query->condition($value['key'], $value['value'], $value['operator']);
        }
        else {
          $query->condition($key, $value);
        }
      }
    }

    return $query->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function aggregate($entity_id, array $aggregates = [], $group_by = NULL, array $conditions = []) {
    $query = $this->entityTypeManager->getStorage($entity_id)->getAggregateQuery();

    if (!empty($group_by)) {
      $query->groupBy($group_by);
    }
    if (!empty($aggregates)) {
      foreach ($aggregates as $key => $value) {
        $query->aggregate($key, $value);
      }
    }
    if (!empty($conditions)) {
      foreach ($conditions as $key => $value) {
        if (is_array($value)) {
          $query->condition($value['key'], $value['value'], $value['operator']);
        }
        else {
          $query->condition($key, $value);
        }
      }
    }

    $result = $query->execute();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesInfo(array $conditions = []) {
    $entities = [];
    $definitions = $this->entityTypeManager->getDefinitions();

    if (!empty($definitions)) {
      foreach ($definitions as $definition) {
        if ($definition instanceof ContentEntityType) {
          // Collect entity info.
          $entities += self::getEntityAndBundlesInfo($definition, $conditions);
        }
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityAndBundlesInfo(EntityTypeInterface $entity, array $conditions = []) {
    // Entity data.
    $info = [];
    $entity_id = $entity->id();
    $info[$entity_id] = self::getEntityInfo($entity, $conditions);
    // Entity bundles.
    $entity_type = $entity->getBundleEntityType();
    if (!empty($entity_type)) {
      $bundles = $this->entityTypeManager->getStorage($entity_type)->loadMultiple();
      if (!empty($bundles)) {
        foreach ($bundles as $bundle) {
          // Collect bundle info.
          $info[$entity_id]['bundles'][] = self::getBundleInfo($entity, $bundle, $conditions);
        }
      }
    }

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityInfo(EntityTypeInterface $entity, array $conditions = []) {
    return [
      'id'    => $entity->id(),
      'label' => $entity->getLabel(),
      'count' => self::countEntityElements($entity, $conditions),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleInfo(EntityTypeInterface $entity, $bundle, array $conditions = []) {
    return [
      'id'    => $bundle->id(),
      'label' => $bundle->label(),
      'count' => self::countBundleElements($entity, $bundle, $conditions),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function countEntityElements(EntityTypeInterface $entity, array $conditions = []) {
    return self::count($entity->id(), $conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function countBundleElements(EntityTypeInterface $entity, $bundle, array $conditions = []) {
    $bundle_field = $entity->get('entity_keys');
    if (!empty($bundle_field['bundle'])) {
      $conditions += [$bundle_field['bundle'] => $bundle->id()];

      return self::count($entity->id(), $conditions);
    }

    return 0;
  }

  /**
   * Get first date creation content for entity type.
   */
  public function getFirstDateContent($entity_id) {
    if ($storage = $this->entityTypeManager->getStorage($entity_id)) {
      $query = $storage->getQuery();
      $query->condition('created', '0', '>');
      $query->sort('created', 'ASC');
      $query->range(0, 1);

      $id = $query->execute();
      if (!empty($id)) {
        $id = reset($id);
        if ($entity = $storage->load($id)) {
          $date = $entity->getCreatedTime();
        }
      }
    }

    return isset($date) ? $date : time();
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

}
