<?php

namespace Drupal\mixitup_views;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Performs assistance functionality.
 *
 * @package Drupal\mixitup_views
 */
class MixitupFunc {
  /**
   * Static array for store active nodes terms.
   *
   * @var array
   */
  protected static $populatedFilters = [];
  /**
   * Static array for store information about which nodes have a specific tid.
   *
   * @var array
   */
  protected static $nodeFilters = [];
  /**
   * Default options service.
   *
   * @var null
   */
  protected $defaultOptionsService;

  /**
   * EntityTypeManager service.
   *
   * @var object
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\mixitup_views\MixitupViewsDefaultOptionsService $defaultOptionsService
   *   MixitupViewsDefaultOptionsService service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManagerService
   *   EntityTypeManager service.
   */
  public function __construct(MixitupViewsDefaultOptionsService $defaultOptionsService, EntityTypeManager $entityTypeManagerService) {
    $this->defaultOptionsService = $defaultOptionsService;
    $this->entityTypeManager = $entityTypeManagerService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mixitup_views.default_options_service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get classes string for node.
   *
   * @param int $nid
   *   Node id.
   *
   * @return string
   *   Classes string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRowClasses($nid) {
    $tids = $this->getNodeTids($nid);
    $classes = [];
    if (!empty($tids)) {
      foreach ($tids as $tid) {
        $classes[] = 'tid_' . $tid;
        $this->populateFilters($tid, $nid);
      }
    }
    $classes = implode(' ', $classes);

    return $classes;
  }

  /**
   * Get all node's taxonomy ids.
   *
   * @param int $nid
   *   Node id.
   *
   * @return array
   *   Array of tids.
   */
  public function getNodeTids($nid) {
    $tids = db_select('taxonomy_index', 'ti')
      ->fields('ti', ['tid', 'nid'])
      ->condition('ti.nid', $nid)
      ->execute()->fetchAllKeyed();

    return array_keys($tids);
  }

  /**
   * Populates structured array of used taxonomy terms.
   *
   * @param int $tid
   *   Taxonomy id.
   * @param int $nid
   *   Node id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function populateFilters($tid, $nid) {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
    /* @var \Drupal\taxonomy\Entity\Term $term */
    if (!empty($term)) {
      $vid = $term->bundle();
      self::$populatedFilters[$vid]['.tid_' . $tid] = $term->getName();
      $this->populateNodeFilters($nid, $tid);
    }
  }

  /**
   * Collects information regarding wich nodes have a specific tid.
   *
   * @param int $nid
   *   Node id.
   * @param int $tid
   *   Taxonomy id.
   */
  public function populateNodeFilters($nid, $tid) {
    self::$nodeFilters[$tid][] = $nid;
  }

  /**
   * Gets populated filters.
   *
   * @return array
   *   Array with structure item[vid]['tid_{tid}'] = term_name.
   */
  public function getPopulatedFilters() {
    return self::$populatedFilters;
  }

  /**
   * Gets populated node filters.
   *
   * @return array
   *   Array with structure item[tid] => array(nids).
   */
  public function getPopulatedNodeFilters() {
    return self::$nodeFilters;
  }

  /**
   * Get default mixitup options.
   *
   * @param bool $convert
   *   Convert check.
   *
   * @return mixed
   *   Array of default options.
   */
  public function getDefaultOptions($convert = NULL) {
    return $this->defaultOptionsService->defaultOptions($convert);
  }

  /**
   * Checks is mixitup js file exists.
   *
   * @return bool
   *   True or False.
   */
  public function isMixitupInstalled() {
    if (is_file('libraries/mixitup/dist/mixitup.min.js')) {
      return TRUE;
    }
    return FALSE;
  }

}
