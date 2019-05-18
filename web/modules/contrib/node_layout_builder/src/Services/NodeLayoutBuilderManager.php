<?php

namespace Drupal\node_layout_builder\Services;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\node_layout_builder\Helpers\NodeLayoutBuilderHelper;

/**
 * Class NodeLayoutBuilderManager.
 *
 * @package Drupal\node_layout_builder
 */
class NodeLayoutBuilderManager {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;
  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $pluginManagerBlock;

  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Constructor.
   */
  public function __construct(AccountProxy $current_user, CurrentRouteMatch $current_route_match, ConfigFactory $config_factory, EntityManager $entity_manager, EntityTypeManagerInterface $entity_type_manager, BlockManager $plugin_manager_block, LoggerChannelFactory $loggerFactory) {
    $this->currentUser = $current_user;
    $this->currentRouteMatch = $current_route_match;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManagerBlock = $plugin_manager_block;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Load entity data.
   *
   * @param int $nid
   *   NID entity.
   *
   * @return array
   *   Entity data element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadEntityDataElementById($nid) {
    $data = [];

    $query = \Drupal::entityQuery('node_layout_builder')
      ->condition('entity_id', $nid, '=')
      ->range(0, 1);
    $nids = $query->execute();

    if (count($nids) > 0) {
      $entities = \Drupal::entityTypeManager()
        ->getStorage('node_layout_builder')
        ->loadMultiple($nids);
      $entity = reset($entities);
      $data = $entity->get('data')->getValue()[0];
    }

    return $data;
  }

  /**
   * Load data of element.
   *
   * @param int $nid
   *   NID entity.
   *
   * @return array|mixed
   *   Data element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadDataElement($nid) {
    $data = NodeLayoutBuilderHelper::getCache($nid);
    if (!$data) {
      $data = self::loadEntityDataElementById($nid);
      if (count($data) > 0) {
        NodeLayoutBuilderHelper::setCache($nid, $data);
      }
    }

    return $data;
  }

  /**
   * Helper function to check if node_layout_builder enabled for bundle.
   *
   * @param string $bundle
   *   The bundle machine name.
   *
   * @return int
   *   Either 1 or 0, depending on status.
   */
  public static function nodeLayoutBuilderIsEnabled($bundle) {
    return \Drupal::config('node_layout_builder.content_type.' . $bundle)
      ->get('enabled');
  }

  /**
   * Helper function to retrieve the node object from the current route.
   *
   * @param int $nid
   *   The nid of node.
   *
   * @return object
   *   The node object, if it exists.
   */
  public function getCurrentNode($nid = 0) {
    if ($nid == 0) {
      // If no node ID is loaded, try to retrieve it from the request.
      // This is used by LayoutEditorBuilder.
      $node = $this->currentRouteMatch->getParameter('node');
    }
    else {
      // A provided URL is used when saving new layout data.
      // $node = Node::load($nid);
      $node = NodeLayoutBuilderHelper::loadNodeById($nid);
    }

    // The following if statement is an edge case for the "revisions" view.
    if (is_numeric($node)) {
      $node = NodeLayoutBuilderHelper::loadNodeById($node);
    }

    return $node;
  }

  /**
   * Checks access for a specific request.
   *
   * @return string
   *   The node id, or 0, if not eligible.
   */
  public function isBuilderEnabled() {
    $allowed = FALSE;

    if ($this->currentUser->hasPermission('use node layoud builder')) {
      $allowed = TRUE;
    }

    return $allowed;
  }

}
