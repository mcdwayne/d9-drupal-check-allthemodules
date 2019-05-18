<?php

namespace Drupal\facets_taxonomy_path_processor\Plugin\facets\processor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FacetsUrlProcessor, which can be configured on the Facet source.
 *
 * @FacetsProcessor(
 *   id = "taxonomy_path_processor_handler",
 *   label = @Translation("Taxonomy path processor handler"),
 *   description = @Translation("Additionally set facet items active if matching a taxonomy url."),
 *   stages = {
 *     "pre_query" = 50,
 *   },
 *   locked = false
 * )
 */
class TaxonomyPathProcessorHandler extends ProcessorPluginBase implements PreQueryProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RouteMatchInterface $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    if ($taxonomy_term = $this->currentRouteMatch->getParameter('taxonomy_term')) {
      $facet->setActiveItem($taxonomy_term->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    $data_definition = $facet->getDataDefinition();
    // @Notice: This differs from other processors
    if ($data_definition->getDataType() === 'field_item:entity_reference') {
      return TRUE;
    }
    if ($data_definition->getDataType() === 'entity_reference') {
      return TRUE;
    }
    if (!($data_definition instanceof ComplexDataDefinitionInterface)) {
      return FALSE;
    }

    $data_definition = $facet->getDataDefinition();
    $property_definitions = $data_definition->getPropertyDefinitions();
    foreach ($property_definitions as $definition) {
      if ($definition instanceof DataReferenceDefinitionInterface
        && $definition->getDataType() === 'entity_reference'
        && $definition->getConstraint('EntityType') === 'taxonomy_term'
      ) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
