<?php

/**
 * @file
 * Contains G2 Requirements.
 *
 * @copyright 2005-2015 Frédéric G. Marand, for Ouest Systemes Informatiques.
 */

namespace Drupal\g2;


use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Class Requirements contains the hook_requirements() checks.
 */
class Requirements implements ContainerInjectionInterface {

  /**
   * The g2.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $g2Config;

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  protected $result = [];

  protected $routeProvider;

  /**
   * The statistics.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $statisticsConfig;

  /**
   * Requirements constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $g2_config
   *   The g2.settings configuration.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The router.route_provider service.
   * @param \Drupal\Core\Config\ImmutableConfig $statistics_config
   *   The statistics.settings configuration.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module_handler service.
   */
  public function __construct(ImmutableConfig $g2_config, RouteProviderInterface $route_provider,
    ImmutableConfig $statistics_config, ModuleHandlerInterface $module_handler) {
    $this->g2Config = $g2_config;
    $this->routeProvider = $route_provider;
    $this->statisticsConfig = $statistics_config;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   *
   * @return static
   *   A new instance every time.
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $container->get('module_handler');

    /* @var \Drupal\Core\Routing\RouteProvider $route_provider */
    $route_provider = $container->get('router.route_provider');

    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');

    $g2_config = $config_factory->get('g2.settings');
    $statistics_settings = $config_factory->get('statistics.settings');

    return new static($g2_config, $route_provider, $statistics_settings, $module_handler);
  }

  /**
   * Check whether a node is valid.
   *
   * @param string $key
   *   The config key for the route to validate.
   * @param \Drupal\Component\Render\MarkupInterface $title
   *   The requirement check title.
   *
   * @return array
   *   A hook_requirements value.
   */
  protected function checkNid($key, MarkupInterface $title) {
    $result = ['title' => $title];
    $main = $this->g2Config->get($key);

    assert('is_numeric($main)');
    if ($main) {
      $node = Node::load($main);
      if (!($node instanceof NodeInterface)) {
        $result += [
          'value' => t('The chosen node must be a valid one, or 0: "@nid" is not a valid node id.',
            ['@nid' => $main]),
          'severity' => REQUIREMENT_ERROR,
        ];
      }
      else {
        $url = Url::fromRoute('entity.node.canonical', ['node' => $main])
          ->toString();
        $result += [
          'value' => t('Valid node: <a href=":url">:url</a>', [':url' => $url]),
          'severity' => REQUIREMENT_OK,
        ];
      }
    }
    else {
      $result += [
        'value' => t('Node set to empty'),
        'severity' => REQUIREMENT_INFO,
      ];
    }
    return $result;
  }

  /**
   * Check whether a route is valid.
   *
   * @param string $key
   *   The config key for the route to validate.
   * @param \Drupal\Component\Render\MarkupInterface $title
   *   The requirement check title.
   *
   * @return array
   *   A hook_requirements() value.
   */
  protected function checkRoute($key, MarkupInterface $title) {
    $result = ['title' => $title];
    $name = $this->g2Config->get($key);

    $arguments = ['%route' => $name];
    try {
      $route = $this->routeProvider->getRouteByName($name);
      if ($route->hasOption('parameters')) {
        $value = t('Valid parametric route %route', $arguments);
      }
      else {
        $value = t('Valid static route <a href="url">%route</a>', $arguments + [
            ':url' => Url::fromRoute($name)->toString(),
        ]);
      }
      $result += [
        'value' => $value,
        'severity' => REQUIREMENT_OK,
      ];
    }
    catch (RouteNotFoundException $e) {
      $result += array(
        'value' => t('The chosen route is not available: %route', $arguments),
        'severity' => REQUIREMENT_ERROR,
      );
    }
    return $result;
  }

  /**
   * Perform controller requirements checks.
   */
  public function checkControllers() {
    $this->result['main.nid'] = $this->checkNid('controller.main.nid',
      t('G2 main page node id'));
    $this->result['main.route'] = $this->checkRoute('controller.main.route',
      t('G2 main page route'));
    $this->result['homonyms.nid'] = $this->checkNid('controller.homonyms.nid',
      t('G2 homonyms disambiguation page node id'));
    $this->result['homonyms.route'] = $this->checkRoute('controller.homonyms.route',
      t('G2 homonyms disambiguation page route'));
  }

  /**
   * Helper for checkStatistics(): build the check data.
   *
   * @return array
   *   - stats
   *   - count
   *   - severity
   *   - value
   */
  protected function prepareStatisticCheck() {
    $stats = $this->moduleHandler->moduleExists('statistics');
    $count = $this->statisticsConfig->get('count_content_views');

    if (!$stats && !$count) {
      // This one is a (questionable) choice.
      $severity = REQUIREMENT_INFO;
      $value = t('G2 statistics disabled.');
    }
    elseif ($stats xor $count) {
      // This one is inconsistent.
      $severity = REQUIREMENT_WARNING;
      $value = t('G2 statistics incorrectly configured.');
    }
    else {
      // Both on: optimal.
      $severity = REQUIREMENT_OK;
      $value = t('G2 statistics configured correctly.');
    }

    return [$stats, $count, $severity, $value];
  }

  /**
   * Perform statistics-related requirements checks.
   */
  public function checkStatistics() {
    list($stats, $count, $severity, $value) = $this->prepareStatisticCheck();
    $items = array();
    $modules_url = [
      ':link' => Url::fromRoute('system.modules_list', [], [
        'fragment' => 'module-statistics',
      ])->toString(),
    ];
    $items[] = $stats
      ? t('<a href=":link">Statistics module</a> installed and activated: OK.', $modules_url)
      : t('<a href=":link">Statistics module</a> not installed or not activated.', $modules_url);
    $link_text = $count ? t('ON') : t('OFF');
    if ($stats) {
      $stats_url = [
        ':stats_url' => Url::fromRoute('statistics.settings', [], [
          'fragment' => 'edit-content',
        ])->toString(),
      ];
      $items[] = t('Count content views" setting is <a href=":stats_url">@on_off</a>',
        $stats_url + [
        '@on_off' => $link_text,
        ]
      );
    }
    else {
      $items[] = t('G2 relies on statistics.module to provide data for the G2 "Top" block and XML-RPC service.
If you do not use either block, you can leave statistics.module disabled.');
    }
    $description = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    $this->result['statistics'] = [
      'title' => t('G2 Statistics'),
      'value' => $value,
      'description' => $description,
      'severity' => $severity,
    ];
  }

  /**
   * Return the check results.
   *
   * @return array
   *   In the hook_requirements() format.
   */
  public function getResult() {
    return $this->result;
  }

}
