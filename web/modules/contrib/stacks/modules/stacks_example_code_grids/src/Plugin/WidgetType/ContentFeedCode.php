<?php

namespace Drupal\stacks_example_code_grids\Plugin\WidgetType;

use Drupal\stacks_content_feed\Plugin\WidgetType\ContentFeed;

/**
 * ContentFeedCode.
 *
 * @WidgetType(
 *   id = "content_feed_code",
 *   label = @Translation("Content Feed Code"),
 * )
 */
class ContentFeedCode extends ContentFeed {

  /**
   * ContentFeedCode constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  function __construct(array $configuration, $plugin_id, $plugin_definition) {
    // Set the unique id for this grid.
    $this->setNoneEntity($configuration['unique_id']);

    $this->grid_options = [
      'per_page' => 8,
      'content_types' => ['page'],
      'order_by' => 'title_asc',
      'pagination_type' => 'default',
      'ajax_on_page_load' => FALSE,
    ];

    // Set the theme template to use. In this case we are using the default
    // content feed template.
    $this->template = 'article_content_default';

    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * Modify the render array before output.
   *
   * This is used for the initial display and also all AJAX requests.
   * @param array $render_array
   * @param array $active_filters
   * @param bool $is_ajax
   */
  public function modifyRenderArray(&$render_array, $active_filters = [], $is_ajax = FALSE) {
    $this->setFields($render_array);

    // Get the results.
    $query_options = [
      'status' => 1,
      'content_types' => $this->grid_options['content_types'],
      'per_page' => $this->grid_options['per_page'],
      'order_by' => $this->grid_options['order_by'],
    ];

    // Adds results, js/css, and variables to the render array.
    $this->prepareNodeGridAjax($render_array, $is_ajax, $query_options);

    if (isset($render_array['#grid']['ajax_attributes'])) {
      $render_array['#grid']['ajax_attributes']['notentity'] = __CLASS__;
    }

  }

  /**
   * Set template and variables sent to the template. The variables you send
   * depend on the template you are using.
   *
   * To see the available templates, print out the render array from
   * widget.output.inc and then drush cr. You can also go to WidgetData::output()
   * and print out the render array from there to see field values that are
   * returned.
   *
   * Keep in mind that some ajax variables are set from prepareNodeGridAjax().
   */
  private function setFields(&$render_array) {
    // The template we are using is outputting certain variables. Let's set them.
    $render_array['#wrapper_id'] = '';
    $render_array['#wrapper_classes'] = '';
    $render_array['#fields'] = [
      'field_dyn_headline' => t('Custom Content Feed'),
    ];
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
