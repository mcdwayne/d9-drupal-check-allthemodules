<?php

declare(strict_types = 1);

namespace Drupal\entity_route_context\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_route_context\EntityRouteContextRouteHelperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Determines if the route is owned by an entities link template.
 */
class EntityRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Entity route helper.
   *
   * @var \Drupal\entity_route_context\EntityRouteContextRouteHelperInterface
   */
  protected $helper;

  /**
   * Name of context variable.
   */
  protected const CANONICAL_ENTITY = 'canonical_entity';

  /**
   * Name prefix of context variable. Entity type ID to be appended to this.
   */
  protected const CANONICAL_ENTITY_PREFIX = 'canonical_entity:';

  /**
   * Constructs a new NodeRouteContext.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\entity_route_context\EntityRouteContextRouteHelperInterface $helper
   *   Entity route helper.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $route_match, EntityRouteContextRouteHelperInterface $helper) {
    $this->routeMatch = $route_match;
    $this->helper = $helper;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids): array {
    $contexts = [];

    $entityTypeId = $this->helper
      ->getEntityTypeId($this->routeMatch->getRouteName());

    /** @var \Drupal\Core\Entity\EntityInterface|null $entity */
    $entity = NULL;
    if (isset($entityTypeId)) {
      // Only handle parameters casted to entity, return first parameter
      // matching type.
      foreach ($this->routeMatch->getParameters() as $parameter) {
        if ($parameter instanceof EntityInterface && ($parameter->getEntityTypeId() === $entityTypeId)) {
          $entity = $parameter;
          break;
        }
      }
    }

    if ($entity) {
      $cacheability = (new CacheableMetadata())
        ->setCacheContexts(['route']);

      $contextDefinition = EntityContextDefinition::create($entityTypeId)->setRequired(FALSE);
      $context = new Context($contextDefinition, $entity);
      $context->addCacheableDependency($cacheability);
      $contexts[static::CANONICAL_ENTITY] = $context;
      $contexts[static::CANONICAL_ENTITY_PREFIX . $entityTypeId] = $context;
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts(): array {
    $contexts = [];
    // \Drupal\Core\Plugin\Context\ContextDefinition::dataTypeMatches allows us
    // to provide a generic 'entity', it will match on both 'entity' and more
    // specific types like 'entity:node'.
    $contextDefinition = new ContextDefinition('entity', $this->t('Entity from route'));
    $context = new Context($contextDefinition);
    $contexts[static::CANONICAL_ENTITY] = $context;

    $entityTypeIds = array_keys(array_flip($this->helper->getAllRouteNames()));
    $entityTypes = array_combine($entityTypeIds, array_map(function (string $entityTypeId): string {
      return (string) $this->entityTypeManager->getDefinition($entityTypeId)->getLabel();
    }, $entityTypeIds));

    // Some context select fields show in order that we provide.
    asort($entityTypes);

    foreach ($entityTypes as $entityTypeId => $entityTypeLabel) {
      $context = EntityContext::fromEntityTypeId($entityTypeId, $this->t('@entity_type from route', [
        '@entity_type' => $entityTypeLabel,
      ]));
      $contexts[static::CANONICAL_ENTITY_PREFIX . $entityTypeId] = $context;
    }

    return $contexts;
  }

}
