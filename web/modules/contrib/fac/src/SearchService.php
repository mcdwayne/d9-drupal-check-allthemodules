<?php

namespace Drupal\fac;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SearchService.
 *
 * @package Drupal\fac
 */
class SearchService {

  /**
   * The search plugin manager service.
   *
   * @var SearchPluginManager
   */
  protected $searchPluginManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SearchService constructor.
   *
   * @param \Drupal\fac\SearchPluginManager $search_plugin_manager
   *   The SearchPluginManager instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManagerInterface instance.
   */
  public function __construct(SearchPluginManager $search_plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->searchPluginManager = $search_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Return the results based on the given key.
   *
   * @param \Drupal\fac\FacConfigInterface $fac_config
   *   The FacConfig entity.
   * @param string $langcode
   *   The language code.
   * @param string $key
   *   The search key.
   *
   * @return array
   *   The results for the given key.
   */
  public function getResults(FacConfigInterface $fac_config, $langcode, $key) {
    $search_plugin = $this->searchPluginManager->createInstance($fac_config->getSearchPluginId());
    $search_results = $search_plugin->getResults($fac_config, $langcode, $key);
    $results = $this->renderResults($fac_config, $search_results, $langcode);

    return $results;
  }

  /**
   * Renders the results in the configured view mode.
   *
   * @param \Drupal\fac\FacConfigInterface $fac_config
   *   The FacConfig entity.
   * @param array $search_results
   *   The results to render.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   The array of rendered items.
   */
  protected function renderResults(FacConfigInterface $fac_config, array $search_results, $langcode) {
    $results = [];

    foreach ($search_results as $search_result) {
      try {
        $entity = $this->entityTypeManager->getStorage($search_result['entity_type'])->load($search_result['entity_id']);
      }
      catch (InvalidPluginDefinitionException $e) {
        return [];
      }
      $view_builder = $this->entityTypeManager->getViewBuilder($search_result['entity_type']);
      $view_modes = $fac_config->getViewModes();
      $build = $view_builder->view($entity, $view_modes[$search_result['entity_type']], $langcode);
      $results[] = render($build);
    }

    return $results;
  }

}
