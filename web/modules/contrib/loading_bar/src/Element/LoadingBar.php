<?php

namespace Drupal\loading_bar\Element;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for a loading bar.
 *
 * Properties:
 * - #configuration: The loading bar settings array.
 *     - preset: Default prebuilt styles of progress bar for you to choose.
 *         Presets currently available: 'line', 'fan', 'circle', 'bubble',
 *         'rainbow', 'energy', 'stripe' and 'text'.
 *     - type: Set the progress type. can be 'stroke' or 'fill'.
 *     - fill-dir: Growth direction of fill type progress bar, possible value:
 *         - ttb: Top to bottom.
 *         - btt: Bottom to top.
 *         - ltr: Left to right.
 *         - rtl: Right to left.
 *     - stroke-dir: Growth direction of stroke type progress bar, possible
 *         value: 'normal' or 'reverse'.
 *     - img: Image of fill type progress bar, could be a file name or data URI.
 *     - path: SVG Path command, such as 'M10 10L90 10', used both in stroke and
 *         fill type progress bar.
 *     - fill: Fill color, pattern or image when using a fill type progress bar
 *         with custom data-path, could be image, generated patterns or colors.
 *     - fill-background: Fill color of the background shape in fill type
 *         progress bar.
 *     - fill-background-extrude: Size of the background shape in fill type
 *         progress bar.
 *     - stroke: Stroke color or pattern.
 *     - stroke-width: Stroke width of the progress bar.
 *     - stroke-linecap: The starting and ending points of a border on SVG
 *         shapes, possible value:
 *         - butt: (default) Ends the stroke with a sharp 90-degree angle.
 *         - square: Similar to butt except that it extends the stroke beyond
 *             the length of the path.
 *         - round: Add a radius that smooths out the start and end points.
 *     - stroke-trail: Trail color.
 *     - stroke-trail-width: Trail width.
 *     - pattern-size: Specify pattern size; e.g., '100'.
 *     - img-size: Specify image size; e.g., '200,100'.
 *     - bbox: Bounding box of an element; e.g. '10 10 80 10'.
 *     - min: Minimum value.
 *     - max: Maximum value.
 *     - label: Label position, possible value: 'center', 'middle' or 'none'.
 *     - width: The width of the loading bar.
 *     - height: The height of the loading bar.
 *
 * Usage example:
 * @code
 * $form['loading_bar'] = [
 *   '#type' => 'loading_bar',
 *   '#configuration' => [
 *     'preset' => 'circle',
 *     'label' => 'center',
 *     'width' => '300px',
 *     'height' => '300px',
 *   ],
 *   '#value' => 35,
 * ];
 * @endcode
 *
 * @FormElement("loading_bar")
 */
class LoadingBar extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#configuration' => [],
      '#value' => 0,
      '#pre_render' => [
        [get_class($this), 'preRenderLoadingBar'],
      ],
      '#theme' => 'loading_bar',
      '#theme_wrappers' => ['form_element'],
      '#attached' => [
        'library' => ['loading_bar/loading_bar.element.loading_bar'],
      ],
    ];
  }

  /**
   * Prepares a #type 'loading_bar' render element for loading-bar.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The $element with prepared variables ready for loading-bar.html.twig.
   */
  public static function preRenderLoadingBar(array $element) {
    Element::setAttributes($element, ['id', 'name']);

    // Add ldBar attributes.
    $ld_bar_attributes = [
      'preset', 'type', 'fill-dir', 'stroke-dir', 'img', 'path',
      'fill', 'fill-background', 'fill-background-extrude', 'stroke',
      'stroke-width', 'stroke-trail', 'stroke-trail-width', 'pattern-size',
      'img-size', 'bbox', 'min', 'max',
    ];
    foreach ($ld_bar_attributes as $ld_bar_attribute) {
      if (isset($element['#configuration'][$ld_bar_attribute])) {
        $element['#attributes']['data-' . $ld_bar_attribute] = $element['#configuration'][$ld_bar_attribute];
      }
    }

    // Add loading bar progress value.
    if (!empty($element['#value'])) {
      $element['#attributes']['data-value'] = $element['#value'];
    }

    // Add line cap style.
    if (isset($element['#configuration']['stroke-linecap'])) {
      $element['#attributes']['class'][] = 'stroke-linecap-' . $element['#configuration']['stroke-linecap'];
    }

    // Add label style.
    if (isset($element['#configuration']['label'])) {
      $element['#attributes']['class'][] = 'label-' . $element['#configuration']['label'];
    }

    // Add styles.
    if (!empty($element['#configuration']['width'])) {
      $element['#attributes']['style'][] = 'width:' . $element['#configuration']['width'];
    }

    if (!empty($element['#configuration']['height'])) {
      $element['#attributes']['style'][] = 'height:' . $element['#configuration']['height'];
    }

    $element['#attributes']['style'][] = 'margin:auto';

    if (!empty($element['#attributes']['style'])) {
      $element['#attributes']['style'] = implode(';', $element['#attributes']['style']) . ';';
    }

    foreach (['img', 'fill'] as $image_field) {
      if (!empty($element['#attributes']['data-' . $image_field]) && is_string($element['#attributes']['data-' . $image_field]) && Uuid::isValid($element['#attributes']['data-' . $image_field])) {
        $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $element['#attributes']['data-' . $image_field]);
        if ($file !== NULL) {
          $element['#attributes']['data-' . $image_field] = ($file !== NULL) ? file_create_url($file->getFileUri()) : NULL;
        }
      }
    }

    return $element;
  }

}
