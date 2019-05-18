<?php

namespace Drupal\parade_conditional_field;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Parade conditional field entities.
 */
class ParadeConditionalFieldListBuilder extends ConfigEntityListBuilder {

  /**
   * Route matcher service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType) {
    return new static(
      $entityType,
      $container->get('entity.manager')->getStorage($entityType->id()),
      $container->get('current_route_match')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   Route matcher service.
   */
  public function __construct(
    EntityTypeInterface $entityType,
    EntityStorageInterface $storage,
    CurrentRouteMatch $routeMatch
  ) {
    parent::__construct($entityType, $storage);
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove route_match way.
   */
  public function load() {
    $paragraphsType = $this->routeMatch->getParameter('paragraphs_type')->id;

    $entities = $this->storage->loadByProperties(['bundle' => $paragraphsType]);

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, [$this->entityType->getClass(), 'sort']);
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['bundle'] = $this->t('Bundle');
    $header['delta'] = '#';
    $header['layouts'] = $this->t('Layout(s)');
    $header['view_mode'] = $this->t('View mode');
    $header['classes'] = $this->t('Classes');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['bundle'] = $entity->getBundle();
    $row['delta'] = $entity->getNumericId();
    $row['layouts'] = implode(', ', $entity->getLayouts());
    $row['view_mode'] = $entity->getViewMode();
    $row['classes'] = implode(', ', $entity->getClasses());
    return $row + parent::buildRow($entity);
  }

}
