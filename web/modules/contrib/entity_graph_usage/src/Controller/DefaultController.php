<?php

namespace Drupal\entity_graph_usage\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_graph\EntityGraphInterface;
use Drupal\entity_graph\EntityGraphNodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefaultController extends ControllerBase {

  /**
   * @var \Drupal\entity_graph\EntityGraphInterface
   */
  protected $entityGraph;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The root entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $rootEntity;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityGraphInterface $entityGraph, EntityRepositoryInterface $entityRepository, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler) {
    $this->entityGraph = $entityGraph;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_graph'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Links and their labels.
   *
   * @var array
   */
  public static $LINKS = [
    'canonical' => 'view',
    'edit-form' => 'edit',
    'entity-graph-usage' => 'usage',
  ];

  /**
   * Usage page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *
   * @return array Renderable array of entity usages.
   *   Renderable array of entity usages.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function list(RouteMatchInterface $route_match) {
    $entity = $this->getEntityFromRouteMatch($route_match);
    $this->setRootEntity($entity);
    $matcher = [$this, 'entityHasPageView'];
    $rows = [];

    $graphNode = $this->entityGraph->getGraphNodeWithNeighbourhood($entity, $matcher);
    $incomingEdges = $graphNode->getIncomingEdges();
    if (!empty($incomingEdges)) {
      foreach ($incomingEdges as $incomingEdge) {
        $relatedGraphNode = $incomingEdge->getSourceNode();
        if ($this->shouldShowItem($relatedGraphNode)) {
          $rows =  array_merge($rows, $this->getTableRowsFromItem($relatedGraphNode, []));
        }
      }
    }

    if (empty($rows)) {
      return [
        '#markup' => $this->t('This entity is not referenced by any other entity.'),
      ];
    }

    $header = [
      $this->t('Title'),
      $this->t('Location(s)'),
      $this->t('View'),
      $this->t('Edit'),
      $this->t('Usage'),
    ];

    $this->moduleHandler->alter('entity_graph_usage_table', $header, $rows);

    return [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTitle(RouteMatchInterface $route_match) {
    $entity = $this->getEntityFromRouteMatch($route_match);
    $bundleEntity = $this->entityTypeManager
      ->getStorage($entity->getEntityType()->getBundleEntityType())
      ->load($entity->bundle());

    $title = $this->formatEntityTitle($entity);
    $bundle = isset($bundleEntity) ? $bundleEntity->label() : $entity->bundle();
    $type = $entity->getEntityType()->getLabel();

    return $this->t('Entity usage of @bundle "@title" (@type)', ['@bundle' => $bundle, '@title' => $title, '@type' => $type]);
  }

  /**
   * Checks if given item should be rendered. Entities that cannot be reached
   * and are not referenced by another entities are filtered out.
   *
   * @param \Drupal\entity_graph\EntityGraphNodeInterface $graphNode
   *   Usage item.
   * @return bool
   */
  protected function shouldShowItem(EntityGraphNodeInterface $graphNode) {
    $hasPage = $graphNode->getEntity()->hasLinkTemplate('canonical');
    $isReferenced = !empty($graphNode->getIncomingEdges());

    // If entity is not embedded anywhere and has no standalone page then hide it.
    if (!$hasPage && !$isReferenced) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Tells if this item should be the last which is displayed in the chain. Once
   * an entity that has a page display (canonical link template) is spotted
   * there's no need to go further.
   *
   * @param EntityInterface $entity
   *   The entity.
   * @return bool
   */
  public function entityHasPageView($entity) {
    return $entity->hasLinkTemplate('canonical');
  }

  /**
   * Returns all table rows associated with given item.
   *
   * @param EntityGraphNodeInterface $graphNode
   *   The graph node object.
   * @param array $route
   *   Visited nodes.
   *
   * @return array
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getTableRowsFromItem(EntityGraphNodeInterface $graphNode, $route) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $graphNode->getEntity();

    $route[] = $entity;

    if ($this->entityHasPageView($entity)) {
      $translation = $this->entityRepository->getTranslationFromContext($entity);
      return [$entity->getEntityTypeId() . ':' . $entity->id() => $this->buildEntityRow($translation, $route)];
    }

    $rows = [];
    $incomingEdges = $graphNode->getIncomingEdges();

    if (!empty($incomingEdges)) {
      foreach ($incomingEdges as $incomingEdge) {
        $relatedGraphNode = $incomingEdge->getSourceNode();
        if ($row = $this->getTableRowsFromItem($relatedGraphNode, $route)) {
          $rows = array_merge($rows, $row);
        }
      }
    }

    return $rows;
  }

  /**
   * Builds a table row representing given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param EntityInterface[] $route
   *   Nesting level.
   *
   * @return array
   *   Single row.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildEntityRow(EntityInterface $entity, $route) {
    array_pop($route);
    $routeWithoutNode = array_reverse($route);
    $formattedRoute = implode(' -> ', array_map([$this, 'formatEntityTitle'], $routeWithoutNode));

    // TODO: Do this based on the edgeType. It needs to be added to entity_graph first.
    if (empty($route) && method_exists($entity, 'getFieldDefinitions')) {
      // Find out the label of which field on the node is being referenced by entity reference.
      foreach ($entity->getFieldDefinitions() as $fieldName => $fieldDefinition) {
        if (in_array($fieldDefinition->getType(), $this->entityGraph->getReferenceFieldTypes())) {
          foreach ($entity->get($fieldName) as $referencedItem) {
            if ($referencedItem->entity->id() === $this->getRootEntity()->id()) {
              $formattedRoute = $fieldDefinition->getLabel();
              break 2;
            }
          }
        }
        if (in_array($fieldDefinition->getType(), $this->entityGraph->getLinkFieldTypes())) {
          /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $item */
          foreach ($entity->get($fieldName) as $linkItem) {
            if ($linkItem->get('uri')->getValue() === "entity:{$this->getRootEntity()->getEntityTypeId()}/{$this->getRootEntity()->id()}") {
              $formattedRoute = $fieldDefinition->getLabel();
              break 2;
            }
          }
        }
      }
    }

    $row['title'] = $entity->toLink($entity->label());
    $row['location'] = $formattedRoute;

    // Display links only for leaf items.
    foreach (static::$LINKS as $rel => $text) {
      if ($entity->hasLinkTemplate($rel) && $this->entityHasPageView($entity)) {
        $row[$text] = $entity->toLink($text, $rel);
      } else {
        $row[$text] = '-';
      }
    }

    $this->moduleHandler->alter('entity_graph_usage_table_row', $row, $entity);

    return $row;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   */
  protected function formatEntityTitle(EntityInterface $entity) {
    if (method_exists($entity, 'getTitle')) {
      $title = $entity->getTitle();
    } elseif (method_exists($entity, 'hasField') && $entity->hasField('title')) {
      $title = $entity->get('title')->value;
    } elseif (method_exists($entity, 'hasField') && $entity->hasField('name')) {
      $title = $entity->get('name')->value;
    } elseif (method_exists($entity, 'getParentEntity') && $parent = $entity->getParentEntity()) {
      // TODO: Figure out what's going on here and in the next if statement :).
      $parent_field = $entity->get('parent_field_name')->value;
      $values = $parent->{$parent_field};
      foreach ($values as $key => $value) {
        if ($value->entity->id() == $entity->id()) {
          return $this->t('@title (@label)', [
            '@title' => $value->getFieldDefinition()->getLabel(),
            '@label' => ucwords($value->entity->getParagraphType()->label()),
          ]);
        }
      }
    }

    if (method_exists($entity, 'getParagraphType')) {
      if (empty($title)) {
        $title = $entity->getParagraphType()->label();
      } else {
        $title = $this->t('@title (@label)', [
          '@title' => $title,
          '@label' => $entity->getParagraphType()->label(),
        ]);
      }
    }

    $this->moduleHandler->alter('entity_graph_usage_entity_title', $title, $entity);

    return $title;
  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_entity_graph_usage_entity_type');
    $entity = $route_match->getParameter($parameter_name);
    return $this->entityRepository->getTranslationFromContext($entity);
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function getRootEntity() {
    return $this->rootEntity;
  }

  /**
   * @param $entity \Drupal\Core\Entity\EntityInterface
   */
  protected function setRootEntity($entity) {
    $this->rootEntity = $entity;
  }

  /**
   * Checks if given usage page is enabled.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function checkAccess(RouteMatchInterface $route_match) {
    /** @var EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    $visibilitySettings = $this->config('entity_graph_usage.settings')->get('entity_types');
    $isVisible = isset($visibilitySettings[$entity->getEntityTypeId()])
      && isset($visibilitySettings[$entity->getEntityTypeId()][$entity->bundle()]);
    return AccessResult::allowedif($isVisible);
  }

}
