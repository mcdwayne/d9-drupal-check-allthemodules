<?php

namespace Drupal\contacts_events\Plugin\Block;

use Drupal\contacts_events\Entity\Event;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a report navigation block.
 *
 * @Block(
 *   id = "ticket_reports_navigation",
 *   admin_label = @Translation("Ticket Reports Navigation"),
 *   category = @Translation("Contacts Events")
 * )
 */
class TicketReportsNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The local task manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new AjaxFormBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   The form builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LocalTaskManagerInterface $local_task_manager, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->localTaskManager = $local_task_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.menu.local_task'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(Event $contacts_event = NULL) {
    $output = [];

    $current_route = $this->routeMatch->getRouteName();
    $tree = $this->localTaskManager->getLocalTasksForRoute($current_route);
    $tasks = array_pop($tree);
    $links = [];

    foreach ($tasks as $task) {
      /* @var \Drupal\Core\Menu\LocalTaskDefault $task */
      $options = [];

      if ($task->getRouteName() == $current_route) {
        $options['attributes']['class'][] = 'is-active';
      }

      $links[] = Link::createFromRoute($task->getTitle(), $task->getRouteName(), $task->getRouteParameters($this->routeMatch), $options);
    }

    // Catch any non existent links.
    /* @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');
    foreach ($links as $key => $link) {
      try {
        $route_name = $link->getUrl()->getRouteName();
        $route_provider->getRouteByName($route_name);
      }
      catch (\Exception $e) {
        unset($links[$key]);
      }
    }

    $output['links'] = [
      '#theme' => 'item_list',
      '#items' => $links,
      '#attributes' => ['class' => ['links']],
    ];

    return $output;
  }

}
