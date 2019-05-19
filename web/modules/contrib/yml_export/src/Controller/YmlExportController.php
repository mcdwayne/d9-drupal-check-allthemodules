<?php

/**
 * @file
 * Contains \Drupal\yml_export\Controller\YmlExportController.
 */

namespace Drupal\yml_export\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for yml_products routes.
 */
class YmlExportController extends ControllerBase {

  public function page() {
    $config = \Drupal::config('yml_export.settings');
    $term_field = $config->get('term_field');
    if (empty($term_field)) {
      die('Please select primary vocabulary on YML export settings page!');
    }

    $ctypes = $config->get('types');
    $enabled_ctypes = array();
    foreach ($ctypes as $type_name => $enabled) {
      if ($enabled) {
        $enabled_ctypes[$type_name] = $type_name;
      }
    }

    if (empty($enabled_ctypes)) {
      die('Please select at least one node type on YML export settings page!');
    }

    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', $ctypes, 'IN');
    $nids = $query->execute();
    $nodes = entity_load_multiple('node', $nids);

    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'catalog');
    $tids = $query->execute();
    $categories = entity_load_multiple('taxonomy_term', $tids);

    $output = array('#theme' => 'yml_products', '#nodes' => $nodes, '#categories' => $categories);
    $output = drupal_render($output);
	return new Response($output, Response::HTTP_OK, array('content-type' => 'application/xml'));
  }

}
