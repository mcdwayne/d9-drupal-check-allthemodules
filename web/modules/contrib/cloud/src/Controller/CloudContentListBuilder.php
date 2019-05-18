<?php

namespace Drupal\cloud\Controller;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides a listing of CloudEntity.
 */
class CloudContentListBuilder extends EntityListBuilder {

  /**
   * The route match class.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match, AccountProxyInterface $current_user) {
    $this->entityTypeId = $entity_type->id();
    $this->storage = $storage;
    $this->entityType = $entity_type;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $header = $this->buildHeader();
    $query = $this->getStorage()->getQuery();

    // Get cloud_context from a path.
    $cloud_context = $this->routeMatch->getParameter('cloud_context');

    if (isset($cloud_context)) {
      $query->tableSort($header)
        ->condition('cloud_context', $cloud_context);
    }
    else {
      $query->tableSort($header);
    }

    $keys = $query->execute();
    return $this->storage->loadMultiple($keys);
  }

  /**
   * Method takes cloud_context into the querying.
   */
  public function render() {

    $header = $this->buildHeader();
    $entities = $this->load();

    $rows = [];
    foreach ($entities as $entity) {
      $rows[] = $this->buildRow($entity);
    }

    $build['pager'] = [
      '#type' => 'pager',
    ];

    $build['tablesort_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#sticky' => TRUE,
      '#rows' => $rows,
      '#empty' => $this->t('There is no @label yet.', [
        '@label' => $this->entityType->getLabel(),
      ]),
    ];

    // Tips by yas 2015/09/28: don't return $build + parent::render()
    // It produces two lists ("$build" + "parent::render") in one page.
    // return $build + parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    foreach ($operations as $key => $operation) {
      if (method_exists($entity, 'getCloudContext')) {
        $operations[$key]['url']
          ->setRouteParameter('cloud_context', $entity->getCloudContext());
      }
    }
    return $operations;
  }

}
