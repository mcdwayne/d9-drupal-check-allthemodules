<?php
namespace Drupal\enterprise_search\Utility;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Plugin\views\argument\Taxonomy;
use Drupal\taxonomy\Plugin\views\wizard\TaxonomyTerm;
use Drupal\user\Entity\User;

class A12Utils {
  public static function generateNodeSolrId($entity_type_id, $index_id = null) {
    $lang = '';
    switch ($entity_type_id) {
        case "node":
            $entity_id = \Drupal::routeMatch()->getParameter('node');
            $node = Node::load($entity_id);
            $lang = $node->get('langcode')->getValue()[0]['value'];
            break;

        case "user":
            $entity_id = \Drupal::request()->attributes->get('user');
            $user = User::load($entity_id);

            $lang = $user->get('langcode')->getValue()[0]['value'];
            break;

        case "taxonomy_term":
            $entity_id = \Drupal::request()->attributes->get('taxonomy_term');
            $taxonomy_term = Term::load($entity_id);

            $lang = $taxonomy_term->get('langcode')->getValue()[0]['value'];
            break;
        case "file":
            $entity_id = \Drupal::request()->attributes->get('file');
            $file = File::load($entity_id);

            $lang = $file->get('langcode')->getValue()[0]['value'];
    }

    $site_hash = \Drupal::config('search_api_solr.settings')->get('site_hash');

    $content_id = is_null($index_id) ?
      'entity:' . $entity_type_id . '/' . $entity_id . ':' . $lang
      :
      $site_hash . '-' . $index_id . '-entity:' . $entity_type_id . '/' . $entity_id . ':' . $lang;
    return $content_id;
  }
}