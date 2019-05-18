<?php

namespace Drupal\opencalais_ui\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for a relevance bar.
 *
 * Properties:
 * - #tag_type: The tag type that will be displayed.
 * - #score: The relevance of the tag within the text.
 *
 * Usage example:
 * @code
 * $build['bar'] = array(
 *   '#type' => 'opencalais_ui_relevance_bar',
 *   '#tag_type' => 'social_tag',
 *   '#score' => 100,
 * );
 * @endcode
 *
 * @RenderElement("opencalais_ui_relevance_bar")
 */
class RelevanceBar extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'opencalais_ui_relevance_bar',
      '#pre_render' => [
        [$class, 'preRenderBar'],
      ],
      '#attached' => [
        'library' => ['opencalais_ui/behaviors'],
      ],
    ];
  }

  /**
   * #pre_render callback for #type 'jw_player'.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   table element.
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderBar($element) {
    switch ($element['#tag_type']) {
      case 'social_tags':
        $score = 0;
        switch ($element['#score']) {
          case 1:
            $score = 100;
            break;
          case 2:
            $score = 66;
            break;
          case 3:
            $score = 33;
            break;
        }
        $element['#width'] = $score;
        $element['#count'] = $score;
        break;
      case 'topic_tags':
      case 'industry_tags':
      case 'entities':
        $element['#width'] = $element['#score'] * 100;
        $element['#count'] = $element['#score'] * 100;
        break;
    }

    return $element;
  }

}
