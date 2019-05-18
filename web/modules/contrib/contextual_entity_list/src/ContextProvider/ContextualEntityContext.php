<?php

namespace Drupal\contextual_entity_list\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Sets the taxonomy term as a context.
 */
class ContextualEntityContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The entity storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $entityType;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
   protected $config;

  /**
   * Constructs a new EntityTypeContext.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager) {
    $this->routerMatch = $route_match;
    $this->configEntity = $config_factory->getEditable('contextual_entity_list.settings')->get('entity_type');
    $this->entityStorage = $entity_manager->getStorage($this->config);
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    if ($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) {
 
      // @TODO: Need to check how router obect constitute the URL structure for an entity-type,
      // then we can take the current entity id and use it in storage.
      //$entity_storage = $this->entityStorage->load();
    }

    $context = new Context(new ContextDefinition("entity:{$this->configEntity}", $this->t('Current Entity')), $entity_storage);
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts([$this->configEntity]);
    $context->addCacheableDependency($cacheability);

    return [
      'entity_context' => $context,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
