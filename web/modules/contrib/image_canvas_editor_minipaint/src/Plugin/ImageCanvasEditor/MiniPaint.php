<?php

namespace Drupal\image_canvas_editor_minipaint\Plugin\ImageCanvasEditor;

use Drupal\image_canvas_editor_api\Plugin\EditorInterface;

/**
 * Defines the minipaint editor.
 *
 * @ImageCanvasEditor(
 *   id = "minipaint",
 *   label = @Translation("Minipaint"),
 * )
 */
class MiniPaint implements EditorInterface {

  /**
   * {@inheritdoc}
   */
  public function renderEditor($image_url) {
    global $base_path;
    return [
      '#theme' => 'mini_paint_editor',
      '#image' => $image_url,
      '#attached' => [
        'library' => [
          'image_canvas_editor_minipaint/drupal-minipaint',
        ],
      ],
      '#minipaint_html' => $base_path . drupal_get_path('module', 'image_canvas_editor_minipaint') . '/js/minipaint.html',
    ];
  }

}
