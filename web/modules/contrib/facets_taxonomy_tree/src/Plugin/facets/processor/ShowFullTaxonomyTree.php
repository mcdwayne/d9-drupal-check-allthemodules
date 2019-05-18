<?php

namespace Drupal\facets_taxonomy_tree\Plugin\facets\processor;

use Drupal\facets\Result\Result;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * A processor that adds an all taxonomy terms to the result array
 *
 * @FacetsProcessor(
 *   id = "show_full_taxonomy_tree",
 *   label = @Translation("Show full taxonomy tree"),
 *   description = @Translation("Show full taxonomy tree regaudless of result. Note this only works on taxonomy facets."),
 *   stages = {
 *     "build" = -20
 *   }
 * )
 */
class ShowFullTaxonomyTree extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface  {

  /**
   * The drupal entity manager
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $config = $this->getConfiguration();

    if (!empty($config['vocabulary']) && !empty($results)) {
      if ($vocab = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($config['vocabulary'])) {
        if ($terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocab->id())) {
          foreach ($terms as $term) {
            $term_exists = FALSE;
            foreach ($results as $result) {
              if ($result->getRawValue() == $term->tid) {
                $term_exists = TRUE;
                break;
              }
            }
            if (!$term_exists) {
              $new_result = new Result($facet, $term->tid, $term->tid, 0);
              if ($facet->isActiveValue($term->tid)) {
                $new_result->setActiveState(TRUE);
              }

              $results[] = $new_result;
            }
          }
        }
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();
    $vocabs = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    $options = [];

    if (!empty($vocabs)) {
      foreach ($vocabs as $key => $vocab) {
        $options[$key] = $vocab->label();
      }
    }

    $build['vocabulary'] = [
      '#title' => $this->t('Vocabulary'),
      '#type' => 'select',
      '#default_value' => isset($config['vocabulary']) ? $config['vocabulary'] : '',
      '#options' => $options,
      '#description' => $this->t('Select the vocabulary that should be rendered for this facet. Note, the field you are choosing for a facet must index taxnomy entity ids'),
    ];

    return $build;
  }

}
