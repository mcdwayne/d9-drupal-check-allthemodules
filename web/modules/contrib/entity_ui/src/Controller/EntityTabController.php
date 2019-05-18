<?php

namespace Drupal\entity_ui\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\entity_ui\Plugin\EntityTabContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Controller for the routes defined by entity tab entities.
 */
class EntityTabController implements ContainerInjectionInterface {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The Entity Tab content plugin manager
   *
   * @var \Drupal\entity_ui\Plugin\EntityTabContentManager
   */
  protected $entityTabContentPluginManager;

  /**
   * Constructs a new EntityTabController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_ui\Plugin\EntityTabContentManager
   *   The entity tab plugin manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The currently active route match object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTabContentManager $entity_tab_content_manager, RouteMatchInterface $current_route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTabContentPluginManager = $entity_tab_content_manager;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_ui_tab_content'),
      $container->get('current_route_match')
    );
  }

  /**
   * Builds the content for the entity tab.
   *
   * @return
   *  A render array.
   */
  public function content() {
    // Get the entity tab ID from the current route.
    // @todo is it possible to set this as a parameter and have it upcasted?
    $entity_tab_id = $this->currentRouteMatch->getRouteObject()->getDefault('_entity_tab_id');
    $entity_tab = $this->entityTypeManager->getStorage('entity_tab')->load($entity_tab_id);

    // Get the target entity the tab is being shown on.
    $target_entity = $this->currentRouteMatch->getParameter($entity_tab->getTargetEntityTypeID());

    // Get the content plugin for the entity tab, and get the content from it.
    $content_plugin = $entity_tab->getContentPlugin();
    return $content_plugin->buildContent($target_entity);
  }

  /**
   * Access callback for the entity tab route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *  The route to check access for.
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *  The route match.
   * @param \Drupal\Core\Session\AccountProxy $account
   *  The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *  The access result.
   */
  public function access(Route $route, RouteMatch $route_match, AccountProxy $account) {
    $entity_tab_id = $route->getDefault('_entity_tab_id');
    $entity_tab = \Drupal::service('entity_type.manager')->getStorage('entity_tab')->load($entity_tab_id);

    // Get the target entity the tab is being shown on.
    $target_entity = $this->currentRouteMatch->getParameter($entity_tab->getTargetEntityTypeID());

    return $entity_tab->access('view', $account, TRUE, $target_entity);
  }

  /**
   * Title callback for the entity tab route.
   *
   * @return string
   *  The page title.
   */
  public function title() {
    $entity_tab_id = $this->currentRouteMatch->getRouteObject()->getDefault('_entity_tab_id');
    $entity_tab = \Drupal::service('entity_type.manager')->getStorage('entity_tab')->load($entity_tab_id);

    // Get the target entity the tab is being shown on.
    $target_entity = $this->currentRouteMatch->getParameter($entity_tab->getTargetEntityTypeID());

    return $entity_tab->getPageTitle($target_entity);
  }

}
