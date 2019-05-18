<?php

namespace Drupal\nodeorder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nodeorder\NodeOrderListBuilder;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic controller to list entities.
 */
class NodeOrderListController extends ControllerBase {

  /**
   * The currently active global container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container, $entity_type_manager) {
    $this->container = $container;
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Provides the listing page for nodes in taxonomy.
   *
   * @param \Drupal\taxonomy\Entity\Term $taxonomy_term
   *   Taxonomy term ID.
   *
   * @return array
   *   A render array.
   */
  public function listing(Term $taxonomy_term) {
    $entity_type = $this->nodeStorage->getEntityType();

    return NodeOrderListBuilder::createInstance($this->container, $entity_type, $taxonomy_term)->render();
  }

}
