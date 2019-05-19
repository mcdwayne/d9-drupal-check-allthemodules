<?php

namespace Drupal\trail_graph\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Class ModalNodePreviewIframe.
 */
class ModalNodePreviewIframe extends ControllerBase {

  /**
   * Render.
   *
   * @return string
   *   Return Hello string.
   */
  public function render(EntityInterface $node_preview, $view_mode_id = 'full') {
    $response = new AjaxResponse();
    $node->in_preview = TRUE;
    $options = [];
    $query = \Drupal::request()->query;
    if ($query->has('destination')) {
      $options['query']['destination'] = $query->get('destination');
      $query->remove('destination');
    }
    $src = Url::fromRoute('trail_graph.simple_node_preview_controller_view', ['node_preview' => $node_preview->id(), 'view_mode_id' => $view_mode_id], $options);
    $content = '<iframe height=\'95%\' width=\'100%\' src="' . \Drupal::request()->getSchemeAndHttpHost() . $src->toString() . '" allowfullscreen></iframe>';
    $response->addCommand(
        new OpenModalDialogCommand(
        $this->t('Preview'),
        $content,
        ['width' => '80%', 'height' => 800]
      )
    );
    return $response;
  }

}
