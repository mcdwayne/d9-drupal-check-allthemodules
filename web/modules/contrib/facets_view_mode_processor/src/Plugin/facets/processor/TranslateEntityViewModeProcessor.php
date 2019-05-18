<?php

namespace Drupal\facets_view_mode_processor\Plugin\facets\processor;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\processor\TranslateEntityProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Transforms the results to show an entity view mode.
 *
 * @FacetsProcessor(
 *   id = "translate_view_mode_entity",
 *   label = @Translation("Transform entity ID to view mode"),
 *   description = @Translation("Display the entity view mode instead of its ID. This only works when an actual entity is indexed, not for the entity id or aggregated fields."),
 *   stages = {
 *     "build" = 6
 *   }
 * )
 */
class TranslateEntityViewModeProcessor extends TranslateEntityProcessor {

  protected $entityDisplayRepository;
  /** @var FacetInterface $facet */
  protected $facet;

  /**
   * TranslateEntityViewModeProcessor constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $language_manager, $entity_type_manager);
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();

    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getEntityType($this->facet));
    $keys = array_keys($view_modes);

    $build['view_mode'] = [
      '#type' => 'select',
      '#options' => $view_modes,
      '#title' => $this->t('View mode'),
      '#default_value' => !empty($config['view_mode']) ? $config['view_mode'] : reset($keys),
      '#required' => TRUE,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $config = $this->getConfiguration();
    $entity_type = $this->getEntityType($facet);

    $language_interface = $this->languageManager->getCurrentLanguage();
    $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);

    /** @var \Drupal\facets\Result\ResultInterface $result */
    $ids = [];
    foreach ($results as $delta => $result) {
      $ids[$delta] = $result->getRawValue();
    }

    // Load all indexed entities of this type.
    $entities = $this->entityTypeManager
      ->getStorage($entity_type)
      ->loadMultiple($ids);

    // Loop over all results.
    foreach ($results as $i => $result) {
      if (!isset($entities[$ids[$i]])) {
        unset($results[$i]);
        continue;
      }

      /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
      $entity = $entities[$ids[$i]];

      // Overwrite the result's display value.
      $build = $view_builder->view($entity, $config['view_mode'], $language_interface->getId());
      $results[$i]->setDisplayValue($build);
    }

    // Return the results with the new display values.
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    $this->facet = $facet;
    return parent::supportsFacet($facet);
  }

  /**
   * Returns entity type related to facet.
   * @param \Drupal\facets\FacetInterface $facet
   * @return mixed
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  protected function getEntityType(FacetInterface $facet) {
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $data_definition */
    $data_definition = $facet->getDataDefinition();

    $property = NULL;
    foreach ($data_definition->getPropertyDefinitions() as $k => $definition) {
      if ($definition instanceof DataReferenceDefinitionInterface && $definition->getDataType() === 'entity_reference') {
        $property = $k;
        break;
      }
    }

    if ($property === NULL) {
      throw new InvalidProcessorException("Field doesn't have an entity definition, so this processor doesn't work.");
    }

    return $data_definition
      ->getPropertyDefinition($property)
      ->getTargetDefinition()
      ->getEntityTypeId();
  }
}
