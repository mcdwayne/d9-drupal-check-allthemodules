<?php

namespace Drupal\views_vcards\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Url;

/**
 * Default style plugin to render one or more vCards.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_vcard_style",
 *   title = @Translation("vCard"),
 *   help = @Translation("Display the results as a vCard"),
 *   theme = "views_vcards_view_vcard",
 *   display_types = {"views_vcard"}
 * )
 */
class ViewsVcardsStylePluginVcard extends StylePluginBase {

  protected $usesOptions = FALSE;
  protected $usesRowPlugin = TRUE;
  protected $usesGrouping = FALSE;

  public function attachTo(array &$build, $display_id, Url $feed_url, $title) {
    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();

    // Add the vCard icon to the view.
    $this->view->feedIcons[] = [
      '#theme' => 'views_vcards_vcard_icon',
      '#url' => $url,
      '#title' => $title,
    ];

  }

  public function render() {
    if (empty($this->view->rowPlugin)) {
      debug('Drupal\views\Plugin\views\style\ViewsVcardsStylePluginVcard: Missing row plugin');
      return [];
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
