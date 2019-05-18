<?php

namespace Drupal\facets_view_mode\Plugin\facets\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

/**
 *
 * @FacetsProcessor(
 *   id = "facets_view_mode",
 *   label = @Translation("Facet view mode "),
 *   description = @Translation("Render Items with entity view mode."),
 *   stages = {
 *     "build" = 100,
 *   },
 * )
 */
class RenderItemsWithViewMode extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  protected $entity_display_repository;

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entity_display_repository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {

    $config = $this->getConfiguration();
    if (!isset($config['view_mode'])) {
      return FALSE;
    }

    $entity_type = $this->getEntityType($facet);
    if (!$entity_type) {
      return FALSE;
    }

    $ids = [];
    foreach ($results as $delta => $result) {
      $ids[$delta] = $result->getRawValue();
    }
    // Load all indexed entities of this type.
    $entities = $this->entityTypeManager
        ->getStorage($entity_type)
        ->loadMultiple($ids);

    $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);

    foreach ($results as $i => $result) {
      $id = $result->getRawValue();
      if (isset($entities[$id])) {
        $rendered = $view_builder->view($entities[$id], $config['view_mode']);
        $results[$i]->setDisplayValue($rendered);
      }
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();

    $entity_type = $this->getEntityType($facet);

    if (!$entity_type) {
      return FALSE;
    }
    $display_modes = $this->entity_display_repository->getViewModes($entity_type);
    $options = [];
    foreach ($display_modes as $name => $view_mode) {
      $options[$name] = $view_mode['label'];
    }

    $build['view_mode'] = [
      '#title' => $this->t('Choose a view mode'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => isset($config['view_mode']) ? $config['view_mode'] : '',
      '#description' => $this->t("Available view modes."),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'), $container->get('entity_display.repository')
    );
  }

  /**
   * Get the entity type name from facet.
   *
   * @param FacetInterface $facet
   * @return string Entity name
   */
  protected function getEntityType(FacetInterface $facet) {
    $data_definition = $facet->getDataDefinition();
    $entity_type = $data_definition->getSetting('target_type');
    return $entity_type;
  }

}
