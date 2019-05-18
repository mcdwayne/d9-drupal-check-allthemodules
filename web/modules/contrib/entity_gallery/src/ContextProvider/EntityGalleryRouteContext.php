<?php

namespace Drupal\entity_gallery\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_gallery\Entity\EntityGallery;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current entity gallery as a context on entity gallery routes.
 */
class EntityGalleryRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new EntityGalleryRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = new ContextDefinition('entity:entity_gallery', NULL, FALSE);
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['entity_gallery'])) {
      if ($entity_gallery = $this->routeMatch->getParameter('entity_gallery')) {
        $value = $entity_gallery;
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'entity_gallery.add') {
      $entity_gallery_type = $this->routeMatch->getParameter('entity_gallery_type');
      $value = EntityGallery::create(array('type' => $entity_gallery_type->id()));
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['entity_gallery'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:entity_gallery', $this->t('Entity gallery from URL')));
    return ['entity_gallery' => $context];
  }

}
