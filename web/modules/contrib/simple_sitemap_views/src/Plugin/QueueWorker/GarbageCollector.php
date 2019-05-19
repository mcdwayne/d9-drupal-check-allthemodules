<?php

/**
 * @file
 * Contains queue worker for garbage collection.
 */

namespace Drupal\simple_sitemap_views\Plugin\QueueWorker;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\simple_sitemap_views\SimpleSitemapViews;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Executes garbage collection in the simple_sitemap_views table.
 *
 * @QueueWorker(
 *   id = "simple_sitemap_views_garbage_collector",
 *   title = @Translation("Garbage collection in the simple_sitemap_views table"),
 *   cron = {"time" = 30}
 * )
 */
class GarbageCollector extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Views sitemap data.
   *
   * @var \Drupal\simple_sitemap_views\SimpleSitemapViews
   */
  protected $simpleSitemapViews;

  /**
   * View entities storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $viewStorage;

  /**
   * GarbageCollector constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\simple_sitemap_views\SimpleSitemapViews $simple_sitemap_views
   *   Views sitemap data.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SimpleSitemapViews $simple_sitemap_views, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->viewStorage = $entity_type_manager->getStorage('view');
    $this->simpleSitemapViews = $simple_sitemap_views;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap_views'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $view_id = $data['view_id'];
    /** @var \Drupal\views\ViewEntityInterface $view_entity */
    $view_entity = $this->viewStorage->load($view_id);
    $display_ids = [];
    // Check that the view exists and it is enabled.
    if ($view_entity && $view_entity->status()) {
      $view = $view_entity->getExecutable();
      $displays = array_filter($view_entity->get('display'), [$this->simpleSitemapViews, 'isValidDisplay']);
      foreach ($displays as $display_id => $display) {
        // Ensure the display was correctly set.
        if (!$view->setDisplay($display_id)) {
          $view->destroy();
          continue;
        }

        // Check that the display is enabled and has indexable arguments.
        if ($view->display_handler->isEnabled() && $args_ids = $this->simpleSitemapViews->getIndexableArguments($view)) {
          $display_ids[] = $display_id;
          // Delete records about sets of arguments that are no longer indexed.
          $args_ids = $this->simpleSitemapViews->getArgumentsStringVariations($args_ids);
          $condition = new Condition('AND');
          $condition->condition('view_id', $view_id);
          $condition->condition('display_id', $display_id);
          $condition->condition('arguments_ids', $args_ids, 'NOT IN');
          $this->simpleSitemapViews->removeArgumentsFromIndex($condition);

          // Check if the records limit for display is exceeded.
          $settings = $this->simpleSitemapViews->getSitemapSettings($view);
          $max_links = is_numeric($settings['max_links']) ? $settings['max_links'] : 0;
          if ($max_links > 0) {
            $condition = new Condition('AND');
            $condition->condition('view_id', $view_id);
            $condition->condition('display_id', $display_id);
            // Delete records that exceed the limit.
            if ($index_id = $this->simpleSitemapViews->getIndexIdByPosition($max_links, $condition)) {
              $condition->condition('id', $index_id, '>');
              $this->simpleSitemapViews->removeArgumentsFromIndex($condition);
            }
          }
        }
      }
      // Delete records about view displays that do not exist or are disabled.
      if (!empty($display_ids)) {
        $condition = new Condition('AND');
        $condition->condition('view_id', $view_id);
        $condition->condition('display_id', $display_ids, 'NOT IN');
        $this->simpleSitemapViews->removeArgumentsFromIndex($condition);
      }
      // Destroy a view instance.
      $view->destroy();
    }

    // Delete records about the view, if it does not exist, is disabled or it
    // does not have a display whose arguments are indexed.
    if (empty($display_ids)) {
      $condition = new Condition('AND');
      $condition->condition('view_id', $view_id);
      $this->simpleSitemapViews->removeArgumentsFromIndex($condition);
    }
  }

}
