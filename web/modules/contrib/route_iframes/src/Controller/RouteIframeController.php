<?php

namespace Drupal\route_iframes\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\NodeInterface;
use Drupal\token\Token;

/**
 * Class RouteIframeController.
 *
 * @package Drupal\route_iframes\Controller
 */
class RouteIframeController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $EntityTypeManager;
  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\token\Token definition.
   *
   * @var \Drupal\token\Token
   */
  protected $token;

  /**
   * Base url definition.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * Route Iframes sub-level tabs definition.
   *
   * @var array
   */
  protected $tabs;

  /**
   * Constructs a new RouteIframeController object.
   */
  public function __construct(EntityTypeManager $entity_manager, QueryFactory $entity_query, AccountProxy $current_user, Token $token) {
    $this->entityTypeManager = $entity_manager;
    $this->entityQuery = $entity_query;
    $this->currentUser = $current_user;
    $this->token = $token;
    $route_iframes_config = $this->config('route_iframes.routeiframesconfiguration');
    $this->baseUrl = $route_iframes_config->get('route_iframe_base_url');
    $this->tabs = $route_iframes_config->get('route_iframe_tabs');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('current_user'),
      $container->get('token')
    );
  }

  /**
   * Build the iframe page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The upcasted node object.
   * @param string $tab
   *   The active tab to check for matching configuration.
   *
   * @return array
   *   The iframe render array or a no match message render array.
   */
  public function build(NodeInterface $node, $tab = '') {
    $active = $this->getRouteConfig($node, $tab);
    if ($active) {
      // Attach the dashboard base_url to the configuration url.
      $config_url = '';
      if (!empty($this->baseUrl)) {
        $config_url = $this->token->replace($active->get('config'), ['node' => $node]);
        $config_url = $this->baseUrl . $config_url;
      }
      $iframe_height = (empty($active->get('iframe_height'))) ? 3000 : $active->get('iframe_height');
      return [
        '#theme' => 'route_iframe',
        '#config' => $config_url,
        '#iframe_height' => $iframe_height,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
    return [
      '#type' => 'markup',
      '#markup' => '<em>This page is not defined.</em>',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * A custom access method to check for config.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object based on the route.
   * @param string $tab
   *   The tab that is being checked.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access object to grant or reject access.
   */
  public function validConfig(NodeInterface $node, $tab = '') {
    $result = AccessResult::allowedIf($this->getRouteConfig($node, $tab))->addCacheableDependency($node);
    if (!empty($tab)) {
      $result->addCacheTags([$tab]);
    }
    else {
      $result->addCacheTags(['route-iframe-empty-tab']);
    }
    return $result;
  }

  /**
   * Utility function to load config within scope.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check the scope against.
   * @param string $tab
   *   The tab that should be used in identifying the config.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   The route iframe config entity or False if not found.
   */
  private function getRouteConfig(NodeInterface $node, $tab = '') {
    $config_ids_query = $this->entityQuery->get('route_iframe_config_entity')
      ->condition('scope_type', 'specific')
      ->condition('scope', $node->id(), 'CONTAINS')
      ->sort('weight');
    if (!empty($tab)) {
      $config_ids_query->condition('tab', $tab);
    }
    $config_ids = $config_ids_query->execute();
    if (!empty($config_ids)) {
      $configs = $this->entityTypeManager->getStorage('route_iframe_config_entity')
        ->loadMultiple($config_ids);
      foreach ($configs as $config) {
        $ids = explode(',', $config->get('scope'));
        if (in_array($node->id(), $ids)) {
          $active = $config;
          break;
        }
      }
    }
    if (!isset($active)) {
      $config_ids_query = $this->entityQuery->get('route_iframe_config_entity')
        ->condition('scope_type', 'bundle')
        ->condition('scope', $node->bundle(), 'CONTAINS')
        ->sort('weight');
      if (!empty($tab)) {
        $config_ids_query->condition('tab', $tab);
      }
      $config_ids = $config_ids_query->execute();
      if (!empty($config_ids)) {
        $configs = $this->entityTypeManager->getStorage('route_iframe_config_entity')
          ->loadMultiple($config_ids);
        foreach ($configs as $config) {
          $bundles = explode(',', $config->get('scope'));
          if (in_array($node->bundle(), $bundles)) {
            $active = $config;
            break;
          }
        }
      }
    }
    if (!isset($active)) {
      $config_ids_query = $this->entityQuery->get('route_iframe_config_entity')
        ->condition('scope_type', 'default')->sort('weight');
      if (!empty($tab)) {
        $config_ids_query->condition('tab', $tab);
      }
      $config_ids = $config_ids_query->execute();
      if (!empty($config_ids)) {
        $active = $this->entityTypeManager->getStorage('route_iframe_config_entity')
          ->load(reset($config_ids));
      }
    }
    if (isset($active)) {
      return $active;
    }
    else {
      return FALSE;
    }
  }

}
