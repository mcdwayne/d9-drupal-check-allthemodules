<?php

namespace Drupal\routes_list\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;

/**
 * Basic controller for routes list report page.
 */
class RoutesListController extends ControllerBase {

  /**
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RoutesListController.
   *
   * @param RouteProviderInterface $route_provider
   *   The route provider.
   * @param PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type handler.
   */
  public function __construct(RouteProviderInterface $route_provider, PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeProvider = $route_provider;
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('user.permissions'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a page with routes overview.
   *
   * @return array
   *   A build array with routes overview table.
   */
  public function report() {

    $build['routes'] = [
      '#type' => 'table',
      '#header' => [$this->t('Path'), $this->t('Access rule')],
      '#sticky' => TRUE,
      '#responsive' => TRUE,
      '#empty' => $this->t('No routes detected.'),
    ];

    // Exclude routes from the list with no-route meaning.
    $blacklist = [
      '<current>',
      '<none>',
      '<nolink>',
    ];

    $modules = $this->moduleHandler->getModuleList();
    $routes_by_module = [];
    $unknown_route_provider = [];
    $permissions = $this->permissionHandler->getPermissions();
    $permission_keys = [
      '_field_ui_view_mode_access',
      '_field_ui_form_mode_access',
      '_permission',
    ];

    foreach ($this->routeProvider->getAllRoutes() as $name => $route) {
      $path = $route->getPath();
      if (in_array($name, $blacklist, TRUE)) {
        continue;
      }

      $requirements = $route->getRequirements();

      if (array_key_exists('_access', $requirements) && $requirements['_access'] === 'TRUE') {
        $access_rule = [
          '#type' => 'inline_template',
          '#template' => '<span class="route-full-access">{{ text }}</span>',
          '#context' => [
            'text' => $this->t('Allowed for anyone'),
          ],
        ];
      }
      elseif (array_key_exists('_user_is_logged_in', $requirements)) {
        if ($requirements['_user_is_logged_in'] === 'TRUE') {
          $access_rule = $this->t('Any logged-in user');
        }
        else {
          $access_rule = $this->t('Only anonymous users');
        }
      }
      elseif (array_intersect($permission_keys, array_keys($requirements))) {
        $key = FALSE;
        foreach ($permission_keys as $suggestion) {
          if (array_key_exists($suggestion, $requirements)) {
            $key = $suggestion;
            break;
          }
        }

        if (array_key_exists($requirements[$key], $permissions)) {
          $perm = $permissions[$requirements[$key]];
          $perm_link = Link::createFromRoute(
            $perm['title'],
            'user.admin_permissions',
            [],
            [
              'fragment' => 'module-' . $perm['provider'],
              'attributes' => ['target' => '_blank'],
            ]
          )->toString();
          $access_rule = $this->t('Permission: @perm', ['@perm' => $perm_link]);
        }
        else {
          $access_rule = $this->t('Unknown permission: @perm', ['@perm' => $requirements[$key]]);
        }
      }
      elseif (array_key_exists('_entity_access', $requirements)) {
        list($entity_type, $op) = explode('.', $requirements['_entity_access']);
        $access_rule = $this->t(
          'Controlled by %op access on %entity_type entity',
          ['%op' => $op,
            '%entity_type' => $this->entityTypeManager->getDefinition($entity_type)
              ->getLabel(),
          ]
        );
      }
      else {
        $access_rule = [
          '#type' => 'inline_template',
          '#template' => '<span title="{{ data }}">{{ text }}</span>',
          '#context' => [
            'text' => $this->t('Custom rule'),
            'data' => serialize($requirements),
          ],
        ];
      }

      $route_info['path'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="route-path">{{ path }}</div>',
        '#context' => ['path' => $path],
        '#wrapper_attributes' => ['class' => 'route-path-td'],
      ];

      // Allow both, raw string and render array as input.
      if (is_array($access_rule)) {
        $route_info['access_rule'] = $access_rule;
      }
      else {
        $route_info['access_rule'] = ['#markup' => $access_rule];
      }

      // We assume route name prefixed with module name.
      // @TODO: We need to improve this with manual collection and tracking of
      // the route info.
      list($module_name,) = explode('.', $name, 2);
      if (array_key_exists($module_name, $modules)) {
        $routes_by_module[$module_name][$path] = $route_info;
      }
      else {
        // Try a fallback for known dynamic routes.
        if (strpos($path, '/devel/') === 0) {
          $routes_by_module['devel'][$path] = $route_info;
        }
        if ($path === '/') {
          $routes_by_module['system'][$path] = $route_info;
        }
        else {
          // @TODO: Handle entity + view overrides.
          $unknown_route_provider[$path] = $route_info;
        }
      }
    }

    foreach ($routes_by_module as $module => $routes) {
      $build['routes'][][$module] = [
        '#wrapper_attributes' => [
          'colspan' => count($build['routes']['#header']),
          'class' => ['routes-section'],
          'id' => 'module-' . $module,
        ],
        '#plain_text' => $this->moduleHandler->getName($module),
      ];

      foreach ($routes as $route_info) {
        $build['routes'][] = $route_info;
      }
    }

    if (!empty($unknown_route_provider)) {
      $build['routes'][]['unknown'] = [
        '#wrapper_attributes' => [
          'colspan' => count($build['routes']['#header']),
          'class' => ['routes-section'],
        ],
        '#markup' => $this->t('Unknown provider'),
      ];

      foreach ($unknown_route_provider as $route_info) {
        $build['routes'][] = $route_info;
      }
    }

    $build['#attached']['library'][] = 'routes_list/routes_list.report';

    return $build;
  }

}
