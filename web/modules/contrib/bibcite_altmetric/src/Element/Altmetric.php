<?php

namespace Drupal\bibcite_altmetric\Element;

use Drupal\Core\Render\Element\HtmlTag;

/**
 * Provides a render element for Altmetric badge, with properties and value.
 *
 * @RenderElement("altmetric")
 */
class Altmetric extends HtmlTag {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [$this, 'preRenderAltmetric'],
      ],
      '#attributes' => [],
      '#value' => NULL,
    ];
  }

  /**
   * Pre-render callback: Renders a HTML div tag with attributes into #markup to show altmetric badge.
   *
   * @param array $element
   *   An associative array containing:
   *   - #badge: string A particular style of badge.
   *   - #source: string Source for badge.
   *   - #source_value: string Value for get concrete badge.
   *   - #condensed: bool Attribute to show a condensed version of the badge.
   *   - #details: array (optional) Attribute to style and position of details. Default: not show(NULL).
   *   - #no_score: bool (optional) Attribute to specify if a score should be not displayed
   *     in the center of the donut style badges. Default: FALSE.
   *   - #new_tab: bool (optional) Attribute to specify if a user should be brought to
   *     a new tab or window when they click on a badge. Default: window(FALSE).
   *
   * @return array
   */
  public static function preRenderAltmetric($element) {
    $element['#attached']['library'][] = 'bibcite_altmetric/altmetric';
    $element['bibcite_altmetric'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['bibcite-altmetric'],
      ],
      '#weight' => $element['#weight'],
      'links' => [
        '#theme' => 'item_list',
        '#attributes' => [
          'class' => ['inline'],
        ],
      ],
    ];
    $element['bibcite_altmetric']['altmetric'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => 'altmetric-embed',
        'data-badge-type' => $element['#badge'],
        $element['#source'] => $element['#source_value'],
        'data-condensed' => $element['#condensed'],
      ],
    ];
    if (isset($element['#details']) && $element['#details']) {
      $element['bibcite_altmetric']['altmetric']['#attributes'][$element['#details']['key']] = $element['#details']['value'];
    }
    if (isset($element['#no_score']) && $element['#no_score']) {
      $element['bibcite_altmetric']['altmetric']['#attributes']['data-no-score'] = 'true';
    }
    if (isset($element['#new_tab']) && $element['#new_tab']) {
      $element['bibcite_altmetric']['altmetric']['#attributes']['data-link-target'] = '_blank';
    }

    return $element;
  }

}
