<?php

namespace Drupal\image_canvas_editor_api\Plugin;

interface EditorInterface {

  /**
   * Should dictate how the editor should be rendered.
   *
   * @return array
   */
  public function renderEditor($image_url);
}
