<?php

namespace Drupal\hn\Plugin\HnEntityManagerPlugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hn\Plugin\HnEntityManagerPluginBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a HN Entity Handler for the View entity.
 *
 * @HnEntityManagerPlugin(
 *   id = "hn_view"
 * )
 */
class ViewHandler extends HnEntityManagerPluginBase {

  protected $supports = 'Drupal\views\Entity\View';

  /**
   * {@inheritdoc}
   */
  public function handle(EntityInterface $entity, $view_mode = 'default') {
    /** @var \Drupal\views\Entity\View $entity */
    /** @var \Drupal\hn\HnResponseService $responseService */
    $responseService = \Drupal::getContainer()->get('hn.response');

    $display = $entity->getDisplay($view_mode);

    $executable = $entity->getExecutable();
    $executable->execute();

    $display_view_mode_options = $display['display_options']['row']['options'];
    $results = [];
    // For each view result row.
    foreach ($executable->result as $resultRow) {
      // Is Search API view.
      if (empty($resultRow->_entity)) {
        $entity = $resultRow->_object->getValue();
        if (!empty($display_view_mode_options['view_modes']['entity:' . $entity->getEntityTypeId()][$entity->bundle()])) {
          $display_view_mode = $display_view_mode_options['view_modes']['entity:' . $entity->getEntityTypeId()][$entity->bundle()];
        }
      }
      else {
        $display_view_mode = $display_view_mode_options['view_mode'];
        $entity = $resultRow->_entity;
      }
      if (empty($display_view_mode)) {
        $display_view_mode = 'default';
      }
      $responseService->addEntity($entity, $display_view_mode);
      $results[] = $entity->uuid();
    }
    $response = [];
    $response['display'] = $display['display_options'];
    unset($response['display']['access']);
    unset($response['display']['cache']);
    unset($response['display']['query']);
    unset($response['display']['style']);
    unset($response['display']['row']);
    unset($response['display']['fields']);

    $filters = [];

    foreach ($response['display']['filters'] as $filter) {
      if (!empty($filter['exposed'])) {
        if ($filter['plugin_id'] === 'taxonomy_index_tid') {
          $query = \Drupal::entityQuery('taxonomy_term');
          $query->condition('vid', $filter['vid']);
          $tids = $query->execute();
          $terms = Term::loadMultiple($tids);
          foreach ($terms as $term) {
            $responseService->addEntity($term);
            $filter['options'][] = $term->uuid();
          }
        }
        $filters[] = $filter;
      }
    }

    $response['display']['filters'] = $filters;
    $path = \Drupal::request()->get('path');
    $response['total_items__' . $path] = $executable->pager->total_items;
    $response['results__' . $path] = $results;

    return $response;
  }

}
