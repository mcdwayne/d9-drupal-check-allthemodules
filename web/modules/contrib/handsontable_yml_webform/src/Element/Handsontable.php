<?php
/**
 * @file
 * Contains \Drupal\theme_example\Element\MyElement.
 */

namespace Drupal\handsontable_yml_webform\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an example element.
 *
 * @RenderElement("handsontable")
 */
class Handsontable extends FormElement {

  /**
   * @param string $folder where module is installed
   *
   * @return string if library is missing
   */
  public static function getLibInstructionsIfNeeded($folder) {
    if (!file_exists("$folder/js/handsontable.full.js") || !file_exists("$folder/css/handsontable.full.css")) {
      $msg = t('Please download the Handsontable source zip from %s. Then place <ul><li>the file %s in the folder %s and <li>the file %s in the folder %s.</li></ul>');
      return '<b>' . t('Error') . '</b><br><br>' .
        sprintf($msg, "<a href='https://handsontable.com'>https://handsontable.com</a>", '<em>handsontable.full.js</em>', "<em>$folder/js</em>", '<em>handsontable.full.css</em>', "<em>$folder/css</em>") . '<br>' .
        t('Please note that the Handsontable source is <a href="https://www.drupal.org/node/422996">not allowed to be contained in the handsontable_yml_webform module</a>.');
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#title' => '',
      '#pre_render' => [
        [$class, 'preRenderMyElement'],
      ],
    ];
  }

  /**
   * Prepare the render array for the template.
   *
   * @param array $element to be rendered (Form API)
   *
   * @return array
   */
  public static function preRenderMyElement($element) {
    static $iTable = 0;
    $result = [];
    if ($element['#title']) {
      $result['title'] = [
        '#type' => 'label',
        '#title' => $element['#title'],
        '#title_display' => '',
      ];
    }

    $aExtra = [];
    $aViewSettings = [];
    if (isset($element['#make_existing_data_read_only'])) {
      $aExtra['make_existing_data_read_only'] = TRUE;
    }
    if (isset($element['#background_colors'])) {
      $aExtra['background_colors'] = $element['#background_colors'];
    }
    if (isset($element['#initial_data'])) {
      $aExtra['initial_data'] = $element['#initial_data'];
    }

    if (isset($element['#view_settings'])) {
      if (is_array($element['#view_settings'])) {
        // Form API
        $aViewSettings = $element['#view_settings'];
      }
      else {
        // Webform
        $aViewSettings = json_decode($element['#view_settings'], TRUE) ?: [];
      }
    }
    $settings = [
      'ids' => [$iTable => $element['#id']],
      'data' => [$iTable => !empty($element['#default_value']) ? $element['#default_value'] : NULL],
      'view_settings' => [$iTable => $aViewSettings + $aExtra],
    ];
    $iTable++;


    $result['#attached']['drupalSettings']['handsontable'] = $settings;

    $output = '';
    $output .= '<div class="handsontable-container">' . "\n";
    if (isset($aViewSettings['contextMenu']) && in_array('row_below', $aViewSettings['contextMenu'])) {
      $output .= '<a class="table-add-row" data-action="addRow" href="#addrow" alt="Add a row">Add row</a> ' . "\n";
    }
    if (isset($aViewSettings['contextMenu']) && in_array('col_right', $aViewSettings['contextMenu'])) {
      $output .= '<a class="table-add-col" data-action="addCol" href="#addcol" alt="Add a column">Add column</a> ' . "\n";
    }
    $output .= '<div id="' . $element['#id'] . '-table" class="handsontable"></div>' . "\n";
    $output .= '</div>' . "\n";

    $name = isset($element['#name']) ? $element['#name'] : $element['#id'];
    $value = isset($element['#default_value']) ? Html::escape($element['#default_value']) : '';
    $result['hidden'] = [
      '#type' => 'textfield',
      '#name' => $name,
      '#id' => $element['#id'],
      '#value' => $value,
      '#attributes' => array_merge($element['#attributes'], ['style' => 'display: none']),
    ];

    $folder = drupal_get_path('module', 'handsontable_yml_webform');
    $output .= self::getLibInstructionsIfNeeded($folder);


    $result['handsontable'] = [
      '#type' => 'markup',
      '#markup' => $output,
      '#allowed_tags' => ['div', 'a', 'b', 'br', 'ul', 'li', 'em'],
      '#attached' => ['library' => ['handsontable_yml_webform/handsontable']],
    ];

    return $result;
  }
}
