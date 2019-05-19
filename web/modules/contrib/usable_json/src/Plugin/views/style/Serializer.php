<?php

namespace Drupal\usable_json\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer as RestSerializer;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "serializer_usable_json",
 *   title = @Translation("Serializer Usable JSON"),
 *   help = @Translation("Serializes Usable JSON views row data using the Serializer component."),
 *   display_types = {"data"}
 * )
 */
class Serializer extends RestSerializer {

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $formats = ['usable_json'];

  /**
   * {@inheritdoc}
   */
  public function render() {
    $return = [
      'view_id' => $this->view->id(),
      'view_display_id' => $this->view->current_display,
      'path' => 'views/' . $this->view->id() . '/' . $this->view->current_display,
      'title' => $this->view->getTitle(),
      'total_rows' => $this->view->total_rows,
      'rows' => [],
      'pager' => $this->view->getDisplay()
        ->isPagerEnabled() ? $this->view->getPager() : FALSE,
      'filters' => '',
    ];

    foreach ($this->view->filter as $key => $filter) {
      if ($filter->isExposed()) {
        $return['filters'][$filter->options['expose']['identifier']] = $filter;
      }
    }
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $return['rows'][] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    return $this->serializer->serialize($return, $content_type, ['views_style_plugin' => $this]);
  }

}
