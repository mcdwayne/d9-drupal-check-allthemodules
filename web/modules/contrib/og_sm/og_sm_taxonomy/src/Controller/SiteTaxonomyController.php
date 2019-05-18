<?php

namespace Drupal\og_sm_taxonomy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og_sm_taxonomy\SiteTaxonomyManagerInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Site taxonomy routes.
 */
class SiteTaxonomyController extends ControllerBase {

  /**
   * The site taxonomy manager.
   *
   * @var \Drupal\og_sm_taxonomy\SiteTaxonomyManagerInterface
   */
  protected $siteTaxonomyManager;

  /**
   * Constructs a new SiteTaxonomyController.
   *
   * @param \Drupal\og_sm_taxonomy\SiteTaxonomyManagerInterface $site_taxonomy_manager
   *   The site taxonomy manager.
   */
  public function __construct(SiteTaxonomyManagerInterface $site_taxonomy_manager) {
    $this->siteTaxonomyManager = $site_taxonomy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og_sm_taxonomy.site_manager')
    );
  }

  /**
   * Builds a site vocabulary overview.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The site node.
   *
   * @return array
   *   A renderable array representing the site vocabulary overview.
   */
  public function vocabularyOverview(NodeInterface $node) {
    $build = [
      '#theme' => 'table',
      '#header' => [
        'label' => $this->t('Vocabulary name'),
        'description' => $this->t('Description'),
        'operations' => $this->t('Operations'),
      ],
      '#rows' => [],
      '#empty' => $this->t('No vocabularies available.'),
    ];

    foreach ($this->siteTaxonomyManager->getSiteVocabularies() as $vocabulary) {
      $row = [];
      $row['label'] = $vocabulary->label();
      $row['description'] = $vocabulary->getDescription();
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $this->getVocabularyOperations($node, $vocabulary),
      ];

      $build['#rows'][$vocabulary->id()] = $row;
    }

    return $build;
  }

  /**
   * Helper function that returns operations for a single vocabulary.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary.
   *
   * @return array
   *   An array of operations.
   */
  protected function getVocabularyOperations(NodeInterface $site, VocabularyInterface $vocabulary) {
    $route_parameters = [
      'node' => $site->id(),
      'taxonomy_vocabulary' => $vocabulary->id(),
    ];

    return [
      'list' => [
        'title' => $this->t('List terms'),
        'url' => Url::fromRoute('og_sm_taxonomy.vocabulary.term_overview', $route_parameters),
      ],
      'add' => [
        'title' => $this->t('Add terms'),
        'url' => Url::fromRoute('og_sm_taxonomy.vocabulary.term_add', $route_parameters),
      ],
    ];
  }

}
