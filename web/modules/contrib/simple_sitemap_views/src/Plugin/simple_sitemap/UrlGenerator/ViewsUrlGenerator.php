<?php

/**
 * @file
 * Contains Views URL generator.
 */

namespace Drupal\simple_sitemap_views\Plugin\simple_sitemap\UrlGenerator;

use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simple_sitemap_views\SimpleSitemapViews;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\simple_sitemap\SitemapGenerator;
use Drupal\Core\Database\Query\Condition;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\views\Views;
use Drupal\Core\Url;

/**
 * Views URL generator plugin.
 *
 * @UrlGenerator(
 *   id = "views",
 *   title = @Translation("Views URL generator"),
 *   description = @Translation("Generates URLs for views."),
 *   weight = 10,
 * )
 */
class ViewsUrlGenerator extends UrlGeneratorBase {

  /**
   * Views sitemap data.
   *
   * @var \Drupal\simple_sitemap_views\SimpleSitemapViews
   */
  protected $simpleSitemapViews;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * View entities storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $viewStorage;

  /**
   * An array of index identifiers for deletion.
   *
   * @var array
   */
  protected $indexesToDelete = [];

  /**
   * ViewsUrlGenerator constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   The simple_sitemap.generator service.
   * @param \Drupal\simple_sitemap\SitemapGenerator $sitemap_generator
   *   The simple_sitemap.sitemap_generator service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\simple_sitemap\Logger $logger
   *   The simple_sitemap.logger service.
   * @param \Drupal\simple_sitemap\EntityHelper $entity_helper
   *   The simple_sitemap.entity_helper service.
   * @param \Drupal\simple_sitemap_views\SimpleSitemapViews $simple_sitemap_views
   *   Views sitemap data.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Simplesitemap $generator,
    SitemapGenerator $sitemap_generator,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    Logger $logger,
    EntityHelper $entity_helper,
    SimpleSitemapViews $simple_sitemap_views,
    RouteProviderInterface $route_provider
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $generator,
      $sitemap_generator,
      $language_manager,
      $entity_type_manager,
      $logger,
      $entity_helper
    );
    $this->simpleSitemapViews = $simple_sitemap_views;
    $this->routeProvider = $route_provider;
    $this->viewStorage = $entity_type_manager->getStorage('view');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.sitemap_generator'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.logger'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('simple_sitemap_views'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSets() {
    // Load views with display plugins that use the route.
    $query = $this->viewStorage->getQuery();
    $query->condition('status', TRUE);
    $query->condition("display.*.display_plugin", $this->simpleSitemapViews->getDisplayPathPluginIds(), 'IN');
    $view_ids = $query->execute();
    // If there are no such views, then return an empty array.
    if (empty($view_ids)) {
      return [];
    }

    // Get data sets.
    $data_sets = [];
    /** @var \Drupal\views\ViewEntityInterface $view_entity */
    foreach ($this->viewStorage->loadMultiple($view_ids) as $view_entity) {
      $displays = array_filter($view_entity->get('display'), [$this->simpleSitemapViews, 'isValidDisplay']);
      foreach ($displays as $display_id => $display) {
        $view = Views::executableFactory()->get($view_entity);
        // Ensure the display was correctly set.
        if (!$view->setDisplay($display_id)) {
          $view->destroy();
          continue;
        }

        // Check that the display is enabled and indexed.
        if ($view->display_handler->isEnabled() && $settings = $this->simpleSitemapViews->getSitemapSettings($view)) {
          // View path without arguments.
          $base_data_set = ['view' => $view, 'settings' => $settings];
          $data_sets[] = $base_data_set + ['arguments' => NULL];

          // Process indexed arguments.
          if ($args_ids = $this->simpleSitemapViews->getIndexableArguments($view)) {
            // Form the condition according to the variants of the
            // indexable arguments.
            $args_ids = $this->simpleSitemapViews->getArgumentsStringVariations($args_ids);
            $condition = new Condition('AND');
            $condition->condition('view_id', $view->id());
            $condition->condition('display_id', $view->current_display);
            $condition->condition('arguments_ids', $args_ids, 'IN');
            // Get the arguments values from the index.
            $max_links = is_numeric($settings['max_links']) ? $settings['max_links'] : NULL;
            $indexed_arguments = $this->simpleSitemapViews->getArgumentsFromIndex($condition, $max_links, TRUE);
            // Add the arguments values for processing.
            foreach ($indexed_arguments as $index_id => $arguments_info) {
              $data_sets[] = $base_data_set + [
                'index_id' => $index_id,
                'arguments' => $arguments_info['arguments'],
              ];
            }
          }
        }
      }
    }
    return $data_sets;
  }

  /**
   * {@inheritdoc}
   */
  protected function processDataSet($data_set) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $data_set['view'];
    $settings = $data_set['settings'];
    $args = $data_set['arguments'];

    // Get view display path.
    try {
      $url = $view->getUrl($args);
      $url->setAbsolute();
      if (is_array($args)) {
        $this->cleanRouteParameters($url, $args);
      }
      $path = $url->getInternalPath();
    }
    catch (\InvalidArgumentException $e) {
      $this->markIndexToDelete($data_set);
      return FALSE;
    }
    catch (\UnexpectedValueException $e) {
      $this->markIndexToDelete($data_set);
      return FALSE;
    }

    // Do not include paths that have been already indexed.
    if ($this->batchSettings['remove_duplicates'] && $this->pathProcessed($path)) {
      return FALSE;
    }
    if (is_array($args)) {
      $params = array_merge([$view->id(), $view->current_display], $args);
      $view_result = call_user_func_array('views_get_view_result', $params);
      // Do not include paths on which the view returns an empty result.
      if (empty($view_result)) {
        $this->markIndexToDelete($data_set);
        return FALSE;
      }
    }
    return [
      'url' => $url,
      'lastmod' => NULL,
      'priority' => isset($settings['priority']) ? $settings['priority'] : NULL,
      'changefreq' => !empty($settings['changefreq']) ? $settings['changefreq'] : NULL,
      'images' => [],
      // Additional info useful in hooks.
      'meta' => [
        'path' => $path,
        'view_info' => [
          'view_id' => $view->id(),
          'display_id' => $view->current_display,
          'arguments' => $args,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function processSegment() {
    // Delete records about sets of arguments that are not added to the sitemap.
    if (!empty($this->indexesToDelete)) {
      $condition = new Condition('AND');
      $condition->condition('id', $this->indexesToDelete, 'IN');
      $this->simpleSitemapViews->removeArgumentsFromIndex($condition);
      $this->indexesToDelete = [];
    }
    parent::processSegment();
  }

  /**
   * Clears the URL from parameters that are not present in the arguments.
   *
   * @param \Drupal\Core\Url $url
   *   The URL object.
   * @param array $args
   *   Array of arguments.
   *
   * @throws \UnexpectedValueException.
   *   If this is a URI with no corresponding route.
   */
  protected function cleanRouteParameters(Url $url, array $args) {
    $parameters = $url->getRouteParameters();
    // Check that the number of params does not match the number of arguments.
    if (count($parameters) != count($args)) {
      $route_name = $url->getRouteName();
      $route = $this->routeProvider->getRouteByName($route_name);
      $variables = $route->compile()->getVariables();
      // Remove params that are not present in the arguments.
      foreach ($variables as $variable_name) {
        if (empty($args)) {
          unset($parameters[$variable_name]);
        }
        else {
          array_shift($args);
        }
      }
      // Set new route params.
      $url->setRouteParameters($parameters);
    }
  }

  /**
   * Marks the index to delete.
   *
   * @param array $data_set
   *   Processed data set.
   */
  protected function markIndexToDelete(array $data_set) {
    if (!empty($data_set['index_id'])) {
      $index_id = $data_set['index_id'];
      $this->indexesToDelete[$index_id] = $index_id;
    }
  }

}
