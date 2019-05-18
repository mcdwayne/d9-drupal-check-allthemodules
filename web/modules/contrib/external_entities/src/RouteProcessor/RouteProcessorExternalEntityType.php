<?php

namespace Drupal\external_entities\RouteProcessor;

use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\Routing\Route;

/**
 * Fixes the route info for external entity type field overview pages.
 *
 * Routes to the field overview page of an external entity type require the
 * 'external_entity_type' parameter. The Field UI module however sets this value
 * under the 'bundle' key (for all entity types without bundles).
 *
 * @see \Drupal\field_ui\FieldUI::getRouteBundleParameter()
 * @see \Drupal\field_ui\FieldUI::getOverviewRouteInfo()
 * @see \Drupal\field_ui\Form\EntityDisplayFormBase::form()
 */
class RouteProcessorExternalEntityType implements OutboundRouteProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityTypeRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (empty($parameters['external_entity_type']) && !empty($parameters['bundle'])) {
      $external_entity_type = $this->entityTypeManager->getDefinition($parameters['bundle'], FALSE);
      if ($external_entity_type && $external_entity_type->getProvider() === 'external_entities') {
        $route->setDefault('external_entity_type', $parameters['bundle']);
      }
    }
  }

}
