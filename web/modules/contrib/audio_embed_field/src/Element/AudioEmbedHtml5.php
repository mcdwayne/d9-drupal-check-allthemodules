<?php

namespace Drupal\audio_embed_field\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Template\Attribute;

/**
 * Providers an element design for embedding HTML5 <audio> tags.
 *
 * @RenderElement("audio_embed_html5")
 */
class AudioEmbedHtml5 extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'audio_embed_html5',
      '#provider' => '',
      '#url' => '',
      '#attributes' => ['controls' => ''],
      '#pre_render' => [
        [static::class, 'preRenderHtml5Embed'],
      ],
    ];
  }

  /**
   * Transform the render element structure into a renderable one.
   *
   * @param array $element
   *   An element array before being processed.
   *
   * @return array
   *   The processed and renderable element.
   */
  public static function preRenderHtml5Embed(array $element) {
    $element['#theme'] .= !empty($element['#provider']) ? '__' . $element['#provider'] : '';

    if (isset($element['#attributes']) && is_array($element['#attributes'])) {
      $element['#attributes'] = new Attribute($element['#attributes']);
    }

    return $element;
  }

}
