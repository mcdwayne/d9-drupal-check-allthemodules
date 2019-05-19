<?php

namespace Drupal\visualn_embed\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\embed\Ajax\EmbedInsertCommand;
use Drupal\filter\FilterFormatInterface;

/**
 * Class DrawingEmbedController.
 */
class DrawingWidgetEmbedController extends ControllerBase {

  /**
   * Preview.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return VisualN Drawing placeholder markup for ckeditor
   */
  public function previewWidget(FilterFormatInterface $filter_format, $id) {

    // @todo: set as dependency injections
    $request = \Drupal::request();
    $align = $request->query->get('align');
    $width = $request->query->get('width');
    $height = $request->query->get('height');

    // @todo: add empty drawing template
    $build = [];

    // @todo: extra offset is added after and before the div

    // @todo: load drawing entity and get info

    // @todo: maybe do additional checks (e.g. permissions)
    $entity_id = $id;
    $entity = \Drupal::entityTypeManager()->getStorage('visualn_drawing')->load($entity_id);
    $properties = [
      'align' => $align,
      'width' => $width,
      'height' => $height,
    ];
    if (!empty($entity)) {
      if ($entity->access('view')) {
        $label = $entity->label();
      }
      else {
        // check if user is allowed to view the drawing (and its info)
        $label = t('You don\'t have permissions to access the drawing');
      }

      $build = [
        '#theme' => 'visualn_embed_drawing',
        '#label' => $label,
        '#has_access' => $entity->access('view'),
        '#id' => $id,
        '#properties' => $properties,
      ];
    }
    else {
      $build = [
        '#theme' => 'visualn_embed_drawing',
        '#id' => '',
        '#properties' => $properties,
      ];
    }

    // @todo: check if it is possible/appropriate to render drawing as iframe in ckeditor
/*
    $build = [
      '#type' => 'markup',
      '#markup' => '<iframe src="http://example.com"></iframe>',
    ];
*/

    $response = new AjaxResponse();
    $response->addCommand(new EmbedInsertCommand($build));

    return $response;
  }

}
