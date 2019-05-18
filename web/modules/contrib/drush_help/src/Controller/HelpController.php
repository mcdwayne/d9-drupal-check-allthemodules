<?php

namespace Drupal\drush_help\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\drush_help\DrushHelpInterface;

/**
 * Controller routines for help routes.
 */
class HelpController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The drush help service.
   *
   * @var \Drupal\drush_help\DrushHelpInterface
   */
  protected $drushHelp;

  /**
   * Creates a new HelpController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\drush_help\DrushHelpInterface $drush_help
   *   The drush help service.
   */
  public function __construct(RouteMatchInterface $route_match, DrushHelpInterface $drush_help) {
    $this->routeMatch = $route_match;
    $this->drushHelp = $drush_help;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'), $container->get('drush_help'), $container->get('plugin.manager.help_section')
    );
  }

  /**
   * Prints a page listing general help for a module.
   *
   * @param string $name
   *   A module name to display a help page for.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function helpPage($name) {
    $build = [];
    if ($this->moduleHandler()->implementsHook($name, 'help')) {
      $module_name = $this->moduleHandler()->getName($name);
      $build['#title'] = $module_name;
      // Getting the module information.
      $info = system_get_info('module', $name);
      if ($info['package'] === 'Core (Experimental)') {
        drupal_set_message($this->t('This module is experimental. <a href=":url">Experimental modules</a> are provided for testing purposes only. Use at your own risk.', [':url' => 'https://www.drupal.org/core/experimental']), 'warning');
      }
      // Getting the module help page.
      $temp = $this->moduleHandler()->invoke($name, 'help', ["help.page.$name", $this->routeMatch]);
      if (empty($temp)) {
        $build['top'] = ['#markup' => $this->t('No help is available for module %module.', ['%module' => $module_name])];
      }
      else {
        if (!is_array($temp)) {
          $temp = ['#markup' => $temp];
        }
        $build['top'] = $temp;
        // Adding the drush help section.
        // Loading the drush command file.
        if (module_load_include('inc', $name, $name . '.drush')) {
          // Calling the module hook_drush_command to get the drush command
          // definitions.
          $drush_commands = _drush_help_call_user_func($name . '_drush_command');
          // Creating the drush command help section.
          $drush_help = $this->drushHelp->getDrushCommandsHelp($drush_commands);
          $build['drush_help'] = ['#markup' => $drush_help];
        }
      }

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
    else {
      throw new NotFoundHttpException();
    }
  }

}
