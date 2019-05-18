<?php

namespace Drupal\context_region_embed\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a way to embed a context region into anything.
 *
 * Properties:
 * - #region: The region you want to embed.
 *
 * Usage example:
 * @code
 * $build['examples_link'] = [
 *   '#type' => 'context_region_embed',
 *   '#region' => 'sidebar',
 * ];
 * @endcode
 *
 * @RenderElement("context_region_embed")
 */
class ContextRegionEmbed extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'container',
      '#pre_render' => [
        [$class, 'preRender'],
      ],
    ];
  }

  /**
   * Renders the render element.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The resulting render array.
   */
  public static function preRender(array $element) {
    if (!isset($element['#region'])) {
      throw new \InvalidArgumentException('#region is missing in a #type context_region_embed render array.');
    }
    $region = $element['#region'];
    $build = \Drupal::service('context_region_embed.context_region_renderer')->render([$region]);
    return $build[$region];
  }

}
