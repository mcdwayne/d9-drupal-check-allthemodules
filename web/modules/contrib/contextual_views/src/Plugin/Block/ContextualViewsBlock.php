<?php

namespace Drupal\contextual_views\Plugin\Block;

use Drupal\Component\Utility\Xss;
use Drupal\views\Element\View;
use Drupal\views\Plugin\Block\ViewsBlock;

/**
 * Variant of the views-block that allows for contextual rendering.
 */
class ContextualViewsBlock extends ViewsBlock {

  /**
   * The arguments for the rendered view.
   *
   * @var mixed[]
   */
  protected $args = [];

  /**
   * {@inheritdoc}
   *
   * Customized version that passes on $this->args.
   */
  public function build() {
    $this->view->display_handler->preBlockBuild($this);

    // We ask ViewExecutable::buildRenderable() to avoid creating a render cache
    // entry for the view output by passing FALSE, because we're going to cache
    // the whole block instead.
    if ($output = $this->view->buildRenderable($this->displayID, $this->args, FALSE)) {
      // Before returning the block output, convert it to a renderable array
      // with contextual links.
      $this->addContextualLinks($output);

      // Block module expects to get a final render array, without another
      // top-level #pre_render callback. So, here we make sure that Views'
      // #pre_render callback has already been applied.
      $output = View::preRenderViewElement($output);

      // Override the label to the dynamic title configured in the view.
      if (empty($this->configuration['views_label']) && $this->view->getTitle()) {
        $output['#title'] = ['#markup' => $this->view->getTitle(), '#allowed_tags' => Xss::getHtmlTagList()];
      }

      // When view_build is empty, the actual render array output for this View
      // is going to be empty. In that case, return just #cache, so that the
      // render system knows the reasons (cache contexts & tags) why this Views
      // block is empty, and can cache it accordingly.
      if (empty($output['view_build'])) {
        $output = ['#cache' => $output['#cache']];
      }

      return $output;
    }

    return [];
  }

}
