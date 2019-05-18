<?php

namespace Drupal\nodeorder;

use Drupal\Core\Cache\Cache;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Defines a service that creates & manages node ordering within taxonomy terms.
 */
class NodeOrderManager implements NodeOrderManagerInterface {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Default cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a NodeOrderManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Default cache bin.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, CacheBackendInterface $cache) {
    $this->configFactory = $config_factory;
    $this->termStorage = $entity_manager->getStorage('taxonomy_term');
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function addToList(NodeInterface $node, $tid) {
    // Append new orderable node. Get the cached weights.
    $weights = $this->getTermMinMax($tid);
    db_update('taxonomy_index')
      ->fields(['weight' => $weights['min'] - 1])
      ->condition('nid', $node->id())
      ->condition('tid', $tid)
      ->execute();
    // If new node out of range, push top nodes down filling the order gap
    // this is when old list's min weight is top range
    // except when new orderable node increases range (new list is not even).
    $taxonomy_nids = db_select('taxonomy_index', 'ti')
      ->fields('ti', ['nid'])
      ->condition('ti.tid', $tid)
      ->orderBy('ti.weight')
      ->execute()
      ->fetchCol('nid');

    $new_node_out_of_range = (count($taxonomy_nids) % 2 == 0 && $weights['min'] == -ceil(count($taxonomy_nids) / 2));
    if ($new_node_out_of_range) {
      // Collect top nodes. Note that while the node data is not yet updated
      // in the database, the taxonomy is.
      $top_range_nids = [];
      $previous_weight = $weights['min'] - 2;
      foreach ($taxonomy_nids as $taxonomy_nid) {
        $taxonomy_node_weight = db_select('taxonomy_index', 'i')
          ->fields('i', ['weight'])
          ->condition('tid', $tid)
          ->condition('nid', $taxonomy_nid)
          ->execute()
          ->fetchField();

        if ($taxonomy_node_weight > $previous_weight + 1) {
          break;
        }
        $previous_weight = $taxonomy_node_weight;
        $top_range_nids[] = $taxonomy_nid;
      }
      // Move top nodes down.
      $query = db_update('taxonomy_index');
      $query->expression('weight', 'weight + 1');
      $query->condition('nid', $top_range_nids, 'IN')
        ->condition('tid', $tid)
        ->execute();
    }
    // Make sure the weight cache is invalidated.
    $this->getTermMinMax($tid, TRUE);
  }

  /**
   * Get min and max weight in the term.
   *
   * @param int $tid
   *   Term id.
   * @param bool $reset
   *   Ignore static data.
   *
   * @return array
   *   Array with min and max weights.
   */
  public function getTermMinMax($tid, $reset = FALSE) {
    static $min_weights = [];
    static $max_weights = [];

    if ($reset) {
      $min_weights = [];
      $max_weights = [];
    }

    if (!isset($min_weights[$tid]) || !isset($max_weights[$tid])) {
      $query = db_select('taxonomy_index', 'i')
        ->fields('i', ['tid'])
        ->condition('tid', $tid)
        ->groupBy('tid');
      $query->addExpression('MAX(weight)', 'max_weight');
      $query->addExpression('MIN(weight)', 'min_weight');
      $record = $query->execute()->fetch();

      $min_weights[$tid] = $record->min_weight;
      $max_weights[$tid] = $record->max_weight;
    }

    $weights['min'] = $min_weights[$tid];
    $weights['max'] = $max_weights[$tid];

    return $weights;
  }

  /**
   * {@inheritdoc}
   */
  public function vocabularyIsOrderable($vid) {
    $vocabularies = $this->configFactory->get('nodeorder.settings')->get('vocabularies');
    return !empty($vocabularies[$vid]);
  }

  /**
   * {@inheritdoc}
   */
  public function selectNodes($tids = [], $operator = 'or', $depth = 0, $pager = TRUE, $order = 'n.sticky DESC, n.created DESC', $count = -1) {
    if (count($tids) > 0) {
      // For each term ID, generate an array of descendant term IDs
      // to the right depth.
      $descendant_tids = [];
      if ($depth === 'all') {
        $depth = NULL;
      }
      foreach ($tids as $index => $tid) {
        $term = $this->termStorage->load($tid);
        $tree = $this->termStorage->loadTree($term->getVocabularyId(), $tid, $depth);
        $descendant_tids[] = array_merge([$tid], array_map(function ($value) {
          return $value->id();
        }, $tree));
      }

      if ($operator == 'or') {
        $args = call_user_func_array('array_merge', $descendant_tids);
        $placeholders = db_placeholders($args, 'int');
        $sql = 'SELECT DISTINCT(n.nid), nd.sticky, nd.title, nd.created, tn.weight FROM {node} n LEFT JOIN {node_field_data} nd INNER JOIN {taxonomy_index} tn ON n.vid = tn.vid WHERE tn.tid IN (' . $placeholders . ') AND n.status = 1 ORDER BY ' . $order;
        $sql_count = 'SELECT COUNT(DISTINCT(n.nid)) FROM {node} n INNER JOIN {taxonomy_index} tn ON n.vid = tn.vid WHERE tn.tid IN (' . $placeholders . ') AND n.status = 1';
      }
      else {
        $args = [];
        $query = db_select('node', 'n');
        $query->join('node_field_data', 'nd');
        $query->condition('nd.status', 1);
        foreach ($descendant_tids as $index => $tids) {
          $query->join('taxonomy_index', "tn$index", "n.nid = tn{$index}.nid");
          $query->condition("tn{$index}.tid", $tids, 'IN');
        }
        $query->fields('nd', ['nid', 'sticky', 'title', 'created']);
        // @todo: distinct?
        $query->fields('tn0', ['weight']);
        // @todo: ORDER BY ' . $order;
        // $sql_count = 'SELECT COUNT(DISTINCT(n.nid))
        // FROM {node} n ' . $joins . ' WHERE n.status = 1 ' . $wheres;.
      }

      if ($pager) {
        if ($count == -1) {
          $count = $this->configFactory->get('nodeorder.settings')->get('default_nodes_main');
        }
        $result = pager_query($sql, $count, 0, $sql_count, $args);
      }
      else {
        if ($count == -1) {
          $count = $this->configFactory->get('nodeorder.settings')->get('feed_default_items');
        }

        if ($count == 0) {
          // TODO Please convert this statement to the D7 database API syntax.
          $result = $query->execute();
        }
        else {
          // TODO Please convert this statement to the D7 database API syntax.
          $result = db_query_range($sql, $args);
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function canBeOrdered(NodeInterface $node) {
    $cid = 'nodeorder:can_be_ordered:' . $node->getType();

    if (($cache = $this->cache->get($cid)) && !empty($cache->data)) {
      return $cache->data;
    }
    else {
      $can_be_ordered = FALSE;

      $nodeorder_vocabularies = [];
      foreach ($node->getFieldDefinitions() as $field) {
        if ($field->getType() != 'entity_reference' || $field->getSetting('target_type') != 'taxonomy_term') {
          continue;
        }

        foreach ($field->getSetting('handler_settings')['target_bundles'] as $vocabulary) {
          $nodeorder_vocabularies[] = $vocabulary;
        }
      }

      foreach ($nodeorder_vocabularies as $vid) {
        if (Vocabulary::load($vid)) {
          $can_be_ordered = TRUE;
        }
      }

      // Permanently cache the value for easy reuse.
      $this->cache->set($cid, $can_be_ordered, Cache::PERMANENT, ['nodeorder']);

      return $can_be_ordered;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderableTids(NodeInterface $node, $reset = FALSE) {
    $cid = 'nodeorder:orderable_tids:' . $node->getType();

    if (!$reset && ($cache = $this->cache->get($cid)) && !empty($cache->data)) {
      $tids = $cache->data;
    }
    else {
      $vocabularies = [];
      foreach ($this->configFactory->get('nodeorder.settings')->get('vocabularies') as $vid => $orderable) {
        if ($orderable) {
          $vocabularies[] = $vid;
        }
      }
      if (!empty($vocabularies)) {
        $query = db_select('taxonomy_index', 'i');
        $query->join('taxonomy_term_data', 'd', 'd.tid = i.tid');
        $query->condition('i.nid', $node->id())
          ->condition('d.vid', $vocabularies, 'IN')
          ->fields('i', ['tid']);
        $tids = $query->execute()->fetchCol('tid');
      }
      else {
        $tids = [];
      }
      // Permanently cache the value for easy reuse.
      // @todo this needs to properly clear when node is edited.
      $this->cache->set($cid, $tids, Cache::PERMANENT, ['nodeorder']);
    }

    return $tids;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderableTidsFromNode(NodeInterface $node) {
    $tids = [];
    foreach ($node->getFieldDefinitions() as $field) {
      if ($field->getType() == 'entity_reference' && $field->getSetting('target_type') == 'taxonomy_term') {
        // If a field value is not set in the node object when node_save() is
        // called, the old value from $node->original is used.
        $field_name = $field->getName();
        foreach ($node->getTranslationLanguages() as $langcode) {
          $translated = $node->getTranslation($langcode->getId());
          foreach ($translated->{$field_name} as $item) {
            $term = $item->getValue();
            if (!empty($term['target_id'])) {
              $tids[$term['target_id']] = $term['target_id'];
            }
          }
        }
      }
    }

    return $tids;
  }

  /**
   * {@inheritdoc}
   */
  public function handleListsDecrease($tid) {
    $taxonomy_nids = db_select('taxonomy_index', 'ti')
      ->fields('ti', ['nid'])
      ->condition('ti.tid', $tid)
      ->orderBy('ti.weight')
      ->execute()
      ->fetchCol('nid');
    if (!count($taxonomy_nids)) {
      return;
    }
    $weights = $this->getTermMinMax($tid, TRUE);
    $range_border = ceil(count($taxonomy_nids) / 2);
    // Out of range when one of both new list's border weights is corresponding
    // range border.
    $border_out_of_range = ($weights['min'] < -$range_border || $weights['max'] > $range_border);
    if ($border_out_of_range) {
      $weight = -$range_border;
      foreach ($taxonomy_nids as $nid) {
        db_update('taxonomy_index')
          ->fields(['weight' => $weight])
          ->condition('nid', $nid)
          ->condition('tid', $tid)
          ->execute();
        $weight++;
      }
      // Make sure the weight cache is invalidated.
      $this->getTermMinMax($tid, TRUE);
    }
  }

}
