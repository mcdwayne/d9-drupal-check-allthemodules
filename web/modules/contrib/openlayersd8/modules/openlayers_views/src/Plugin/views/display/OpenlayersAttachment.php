<?php

namespace Drupal\openlayers_views\Plugin\views\display;


use Drupal\views\Plugin\views\display\Attachment;
use Drupal\views\ViewExecutable;

/**
 * The plugin which handles attachment of additional openlayers features to
 * openlayers map views.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "openlayers_attachment",
 *   title = @Translation("Openlayers Attachment"),
 *   help = @Translation("Add additional markers to a openlayers map."),
 * )
 *
 * @todo We only use very few features from the parent class Attachment, so this
 *       should probably just extend DisplayPluginBase to simplify things.
 */
class OpenlayersAttachment extends Attachment {

  /**
   * Whether the display allows the use of a pager or not.
   *
   * @var bool
   */
  protected $usesPager = FALSE;

  /**
   * Whether the display allows the use of a 'more' link or not.
   *
   * @var bool
   */
  protected $usesMore = FALSE;

  /**
   * Whether the display allows area plugins.
   *
   * @var bool
   */
  protected $usesAreas = FALSE;

  /**
   * {@inheritdoc}
   */
  public function usesLinkDisplay() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(ViewExecutable $view, $display_id, array &$build) {
    $displays = $this->getOption('displays');

    if (empty($displays[$display_id])) {
      return;
    }

    if (!$this->access()) {
      return;
    }

    $args = $this->getOption('inherit_arguments') ? $this->view->args : array();
    $view->setArguments($args);
    $view->setDisplay($this->display['id']);
    if ($this->getOption('inherit_pager')) {
      $view->display_handler->usesPager = $this->view->displayHandlers->get($display_id)
        ->usesPager();
      $view->display_handler->setOption('pager', $this->view->displayHandlers->get($display_id)
        ->getOption('pager'));
    }
    if ($render = $view->render()) {
      $this->view->attachment_before[] = $render + array(
          '#openlayers-attachment' => TRUE,
        );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = (!empty($this->view->result) || $this->view->style_plugin->evenEmpty()) ? $this->view->style_plugin->render($this->view->result) : array();

    // The element is rendered during preview only; when used as an attachment
    // in the Openlayers class, only the 'rows' property is used.
    $element = array(
      '#markup' => print_r($rows, TRUE),
      '#prefix' => '<pre>',
      '#suffix' => '</pre>',
      '#attached' => &$this->view->element['#attached'],
      'rows' => $rows,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'openlayers';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Overrides for standard stuff.
    $options['style']['contains']['type']['default'] = 'openlayers_marker_default';
    $options['defaults']['default']['style'] = FALSE;
    $options['row']['contains']['type']['default'] = 'openlayers_marker';
    $options['defaults']['default']['row'] = FALSE;

    return $options;
  }
}
