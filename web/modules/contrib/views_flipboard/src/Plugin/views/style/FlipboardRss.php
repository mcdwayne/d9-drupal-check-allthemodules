<?php

namespace Drupal\views_flipboard\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Default style plugin to render an Flipboard RSS feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "flipboard_rss",
 *   title = @Translation("Flipboard RSS feed"),
 *   help = @Translation("Generates an Flipboard RSS feed from a view."),
 *   theme = "views_view_flipboard_rss",
 *   display_types = {"feed"}
 * )
 */
class FlipboardRss extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to its output.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  public function attachTo(array &$build, $display_id, $path, $title) {
    $display = $this->view->displayHandlers->get($display_id);
    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = _url($this->view->getUrl(NULL, $path), $url_options);
    if ($display->hasPath()) {
      if (empty($this->preview)) {
        $build['#attached']['feed'][] = [$url, $title];
      }
    }
    else {
      $this->view->feedIcons[] = [
        '#theme' => 'feed_icon',
        '#url' => $url,
        '#title' => $title,
      ];
    }
  }

  /**
   * Get RSS feed description.
   *
   * @return string
   *   The string containing the description with the tokens replaced.
   */
  public function getDescription() {
    $description = $this->options['description'];

    // Allow substitutions from the first row.
    $description = $this->tokenizeValue($description, 0);

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (empty($this->view->rowPlugin)) {
      debug('Drupal\views\Plugin\views\style\Flipboard: Missing row plugin');
      return;
    }
    $rows = [];

    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
    ];
    unset($this->view->row_index);
    return $build;
  }

}
