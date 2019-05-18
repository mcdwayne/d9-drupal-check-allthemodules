<?php

namespace Drupal\aframe\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * @RenderElement("aframe_tag")
 */
class AFrameTag extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process'        => [
        [$class, 'processGroup'],
      ],
      '#pre_render'     => [
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['aframe_tag'],
    ];
  }

}
