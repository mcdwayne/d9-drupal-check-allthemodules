<?php

namespace Drupal\imce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\help\HelpSectionManager;

/**
 * Controller routines for help routes.
 */
class ImceHelpController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The help section plugin manager.
   *
   * @var \Drupal\help\HelpSectionManager
   */
  protected $helpManager;

  /**
   * Creates a new HelpController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\help\HelpSectionManager $help_manager
   *   The help section manager.
   */
  public function __construct(RouteMatchInterface $route_match, HelpSectionManager $help_manager) {
    $this->routeMatch = $route_match;
    $this->helpManager = $help_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('plugin.manager.help_section')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $build = [];
    $name = 'imce';
    $build['#theme'] = 'imce_help';
    $module_name = $this->moduleHandler()->getName($name);
    $build['#title'] = 'Imce File Manager Help';
    $temp = $this->moduleHandler()->invoke($name, 'help', ["help.page.$name", $this->routeMatch]);

    if (!is_array($temp)) {
      $temp = ['#markup' => $temp];
      $build['#markup'] = $temp['#markup'];
    }
    $build['top'] = $temp;
    // $build['#videos'][] = '<iframe width="560" height="315" src="https://www.youtube.com/embed/MKE3aLLxN8g?start=273" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';

    // Only print list of administration pages if the module in question has
    // any such pages associated with it.
    $admin_tasks = system_get_module_admin_tasks($name, system_get_info('module', $name));
    if (!empty($admin_tasks)) {
      $links = [];
      foreach ($admin_tasks as $task) {
        $link['url'] = $task['url'];
        $link['title'] = $task['title'];
        $links[] = $link;
      }
      $build['links'] = [
        '#theme' => 'links__help',
        '#heading' => [
          'level' => 'h3',
          'text' => $this->t('@module administration pages', ['@module' => $module_name]),
        ],
        '#links' => $links,
      ];
    }

    return $build;
  }

}
