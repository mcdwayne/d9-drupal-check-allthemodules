<?php

namespace Drupal\taxonomy_facets\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * An example controller.
 */
class TaxonomyFacetsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content($taxo_facets_path, Request $request) {
    $config = \Drupal::config('taxonomy_facets.settings');
    $show_nodes_if_no_filters = $config->get('show_nodes_if_no_filters');


    if ($show_nodes_if_no_filters || $taxo_facets_path !== 'no-argument') {

      $selected_filters = taxonomy_facets_get_selected_filters($taxo_facets_path);
      $filters = array($selected_filters->getAppliedFilterTids());

      $filters = current($filters);
      $getNodes = new \Drupal\taxonomy_facets\GetNodes($filters);
      $nodes = $getNodes->getNodes();

      pager_default_initialize($getNodes->getNumberOfNodes(),
        $config->get('number_of_nodes_per_page')
      );

      if($nodes = node_load_multiple($nodes)) {
        $output = node_view_multiple($nodes);
        $output[] = ['#type' => 'pager'];
      }
      else {
        $output['no_content'] = array(
          '#prefix' => '<p>',
          '#markup' => t('There is currently no content classified with this combination of filters. Try removing one or more filters'),
          '#suffix' => '</p>',
        );
      }
    }
    else {
      $output['no_content'] = array(
        '#prefix' => '<p>',
        '#markup' => t('Please select one or more filters'),
        '#suffix' => '</p>',
      );
    }
    return $output;
  }

  /**
   * Returns a page title.
   */
  public function getTitle() {
   return \Drupal::config('taxonomy_facets.settings')->get('page_title');
  }
}
