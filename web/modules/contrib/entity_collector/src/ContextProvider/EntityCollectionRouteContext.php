<?php

namespace Drupal\entity_collector\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current entity collection as a context on workflow task routes.
 */
class EntityCollectionRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityCollectionRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualifiedContextIds) {
    $result = [];

    $contextDefinition = EntityContextDefinition::create('entity_collection')->setRequired(FALSE);
    $value = NULL;
    if (($routeObject = $this->routeMatch->getRouteObject()) && ($routeContexts = $routeObject->getOption('parameters')) && isset($routeContexts['entity_collection'])) {
      if ($entityCollection = $this->routeMatch->getParameter('entity_collection')) {
        $value = $entityCollection;
      }
    }
    elseif ($this->routeMatch->getRouteName() === 'entity.entity_collection.add_form') {
      $entityCollectionType = $this->routeMatch->getParameter('entity_collection_type');
      $value = $this->entityTypeManager->getStorage('entity_collection')->create(['type' => $entityCollectionType->id()]);
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($contextDefinition, $value);
    $context->addCacheableDependency($cacheability);
    $result['entity_collection'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('entity_collection', $this->t('Entity Collection from URL'));
    return ['entity_collection' => $context];
  }

}
