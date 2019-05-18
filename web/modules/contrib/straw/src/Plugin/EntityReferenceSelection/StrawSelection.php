<?php

namespace Drupal\straw\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;
use Drupal\Core\Cache\Cache;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "straw",
 *   label = @Translation("Straw selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "straw",
 *   weight = 1
 * )
 */
class StrawSelection extends TermSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $options = [];

    $bundles = $this->entityManager->getBundleInfo('taxonomy_term');
    $handler_settings = $this->configuration['handler_settings'];
    $bundle_names = !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : array_keys($bundles);

    foreach ($bundle_names as $bundle) {
      if ($vocabulary = Vocabulary::load($bundle)) {
        foreach ($this->getSelectableTerms($vocabulary->id()) as $term) {

          $referenceable = TRUE;
          if ($match && $match_operator == "CONTAINS") {
            $referenceable = (stripos($term['tree_path'], $match) !== FALSE);
          }
          if ($match && $match_operator == "STARTS_WITH") {
            $referenceable = (stripos($term['tree_path'], $match) === 0);
          }
          if ($match && $match_operator == "=") {
            $referenceable = ($term['tree_path'] == $match);
          }

          if ($referenceable) {
            $options[$vocabulary->id()][$term['tid']] = Html::escape($term['tree_path']);
            if ($limit > 0 && count($options[$vocabulary->id()]) == $limit) {
              break;
            }
          }
        }
      }
    }

    return $options;
  }

  /**
   * Gets the potentially selectable terms for a given bundle.
   *
   * @param string $bundle_name
   *   Vocabulary ID to retrieve terms for.
   *
   * @return array
   *   The searchable data.
   */
  private function getSelectableTerms($bundle_name) {

    $cache_context_keys = \Drupal::service('cache_contexts_manager')->convertTokensToKeys(['user.permissions'])->getKeys();
    $cid = $bundle_name . ':' . implode(':', $cache_context_keys);
    $straw_cache = \Drupal::cache('straw');

    // Load from cache if possible rather than rebuilding the term list.
    if ($cached_data = $straw_cache->get($cid)) {
      return $cached_data->data;
    }

    $all_terms = $this->entityManager->getStorage('taxonomy_term')->loadTree($bundle_name);

    // We want $terms to be keyed by ID rather than numerically.
    $all_terms = array_reduce($all_terms, function ($carry, $item) {
      $carry[$item->tid] = $item;
      return $carry;
    }, []);

    $searchable_data = [];
    foreach ($all_terms as $term) {

      // Build the tree path for the term, including the names of its
      // ancestors. Currently, a single term being in multiple places in the
      // hierarchy is not actively supported (only one possible tree path can
      // get shown in the autocomplete results)
      $tree_path = $term->name;
      $current = $term;
      while (($parent_id = $current->parents[0]) && ($parent = $all_terms[$parent_id])) {
        $tree_path = $parent->name . ' >> ' . $tree_path;
        $current = $parent;
      }

      $searchable_data[] = ['tid' => $term->tid, 'tree_path' => $tree_path];
    }

    // Save into cache for faster loading in the future.
    \Drupal::cache('straw')->set($cid, $searchable_data, Cache::PERMANENT, ['taxonomy_term_list']);

    return $searchable_data;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    // Straw only works with terms; $entity_type_id should always be
    // "taxonomy_term". Since we need term-specific handling here, we ignore
    // that setting (as well as $uid, since terms don't implement
    // EntityOwnerInterface).
    $term_names = explode('>>', $label);

    /** @var \Drupal\straw\NewTermStorage $new_term_storage */
    $new_term_storage = \Drupal::service('straw.new_term_storage');

    // Tracks the deepest term we've already processed.
    $last_term = NULL;

    // Loop to find the term deepest in the existing hierarchy which matches
    // the desired tree path.
    $tree_path = '';
    while ($term_name = array_shift($term_names)) {
      $tree_path .= ($tree_path ? ' >> ' : '') . trim($term_name);
      $matching_terms_by_vocabulary = $this->getReferenceableEntities($tree_path, '=', 1);
      if (empty($matching_terms_by_vocabulary[$bundle])) {
        // We'll process the unmatched term again, below, to create it.
        array_unshift($term_names, $term_name);
        break;
      }
      $last_term = key($matching_terms_by_vocabulary[$bundle]);
    }

    // Create terms of the tree path which have not been found (meaning that
    // they don't exist). There *should* always be at least one of these if
    // this function is getting called, though it should still function if there
    // isn't. The NewTermStorage service is used to track which terms have
    // already been created but which have not yet necessarily been saved, to
    // prevent creating duplicate terms if the same new term is named by
    // multiple term hierarchies being created during the same request.
    while ($term_name = array_shift($term_names)) {
      $tree_path .= ($tree_path ? ' >> ' : '') . trim($term_name);

      if ($found_term = $new_term_storage->get($bundle, $tree_path)) {
        $last_term = $found_term;
      }
      else {
        $last_term = Term::create([
          'vid' => $bundle,
          'name' => trim($term_name),
          'parent' => $last_term,
        ]);
        $new_term_storage->set($bundle, $tree_path, $last_term);
      }

    }

    return $last_term;
  }

}
