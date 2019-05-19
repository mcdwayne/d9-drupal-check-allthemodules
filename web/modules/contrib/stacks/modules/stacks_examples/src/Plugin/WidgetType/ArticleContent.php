<?php

namespace Drupal\stacks_examples\Plugin\WidgetType;

use Drupal\stacks_content_feed\Plugin\WidgetType\ContentFeed;

/**
 * ArticleContent.
 *
 * @WidgetType(
 *   id = "article_content",
 *   label = @Translation("Article Content"),
 * )
 */
class ArticleContent extends ContentFeed {

  /**
   * ArticleContent constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->setEntity($configuration['widget_entity']);

    $this->grid_options = [
      'per_page' => 10,
      'pagination_type' => 'default',
      'content_types' => ['article'],
      'order_by' => 'title_asc',
    ];

    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * Modify the render array before output.
   *
   * This is used for the initial display and also all AJAX requests.
   */
  public function modifyRenderArray(&$render_array, $options = []) {
    $is_ajax = isset($options['is_ajax']) ? $options['is_ajax'] : FALSE;

    // Get the results.
    $query_options = [
      'status' => 1,
      'content_types' => $this->grid_options['content_types'],
      'per_page' => $this->grid_options['per_page'],
      'order_by' => $this->grid_options['order_by'],
    ];

    // Adds results, js/css, and variables to the render array.
    $this->prepareNodeGridAjax($render_array, $is_ajax, $query_options);
  }

  /**
   * Define the fields that should not be sent to the template as variables.
   * These are usually fields on the bundle that you want to handle via
   * programming only.
   */
  public function fieldExceptions() {
    return [];
  }

}
