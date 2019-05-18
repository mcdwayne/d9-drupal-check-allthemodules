<?php

namespace Drupal\onepass\Plugin\views\style;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Default style plugin to render OnePass atoms feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "onepassatoms",
 *   title = @Translation("OnePass atoms feed"),
 *   help = @Translation("Generates OnePass Atoms feed from a view."),
 *   theme = "views_view_onepassatoms",
 *   display_types = {"feed"}
 * )
 */
class OnepassAtomsFeed extends StylePluginBase {

  /**
   * Onepass service.
   *
   * @var \Drupal\onepass\OnepassServiceInterface
   */
  protected $onepass;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->onepass = \Drupal::service('onepass.service');
  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(array &$build, $display_id, Url $feed_url, $title) {
    $display = $this->view->displayHandlers->get($display_id);
    $url_options = array();
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();
    if ($display->hasPath()) {
      if (empty($this->preview)) {
        $build['#attached']['feed'][] = array($url, $title);
      }
    }
    else {
      $this->view->feedIcons[] = array(
        '#theme' => 'feed_icon',
        '#url' => $url,
        '#title' => $title,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = array();

    foreach ($this->view->result as $row) {
      if (isset($row->nid)) {
        if ($node = Node::load($row->nid)) {
          $rows[] = array(
            'title' => $node->label(),
            'link' => $node->toUrl(
              'canonical',
              array('absolute' => TRUE)
            )->toString(),
            'summary' => $this->getNodeDisplay(
              $this->onepass->markForTrim($node)
            ),
            'content' => $this->getNodeDisplay(
              $node
            ),
            'id' => $this->onepass->getShortCodeReplacementUniqueId(
              $node->id()
            ),
            'updated' => $this->onepass->formatDate(
              $node->getChangedTime()
            ),
            'published' => $this->onepass->formatDate(
              $node->getCreatedTime()
            ),
            'author' => array(
              'name' => $node->getOwner()->getDisplayName(),
              'email' => $node->getOwner()->getEmail(),
            ),
          );
        }
      }
    }

    $this->options['updated'] = $this->onepass->formatDate(REQUEST_TIME);
    $this->options['pagination'] = $this->view->pager->render(array());
    $this->options['feed_id'] = $this->onepass
                                  ->getShortCodeReplacementUniqueId(1);

    return array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
    );
  }

  /**
   * Prepare node for display.
   *
   * @param object $node
   *   Node object.
   *
   * @return array
   *   Node rendering array.
   */
  private function getNodeDisplay($node) {
    $controller = \Drupal::entityTypeManager()
                    ->getViewBuilder($node->getEntityTypeId());
    $display = $controller->view($node, 'full');
    $display['#theme'] = 'onepassatoms_node';
    return $display;
  }

}
