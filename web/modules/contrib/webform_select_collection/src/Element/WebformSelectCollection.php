<?php

namespace Drupal\webform_select_collection\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Table;

/**
 * Provides a form element for a table with selects/checkboxes in right column.
 *
 * Properties:
 * - #title: Translated table header label.
 * - #line_items: An associative array of line items for the table.
 * - #line_options: An associative array of select options.
 * - #empty: The message to display if table does not have any options.
 * - #checkbox_collection: Set to TRUE to render the table with checkboxes. If
 *   set to TRUE #line_options are ignored.
 * - #js_select: Set to FALSE if you don't want the select all checkbox added to
 *   the header.
 *
 * Other properties of the \Drupal\Core\Render\Element\Table element are also
 * available.
 *
 * Usage example:
 * @code
 * $line_items = [
 *   'item_1' => $this->t('Line item 1'),
 *   'item_2' => $this->t('Line item 2'),
 * ];
 *
 * $options = [
 *   1 => $this->t('Option 1'),
 *   2 => $this->t('Option 2'),
 * ];
 *
 * $form['table'] = array(
 *   '#type' => 'webform_select_collection',
 *   '#title' => $this->t('Collection label'),
 *   '#options' => $options,
 *   '#line_items' => $line_items,
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Table
 *
 * @RenderElement("webform_select_collection")
 */
class WebformSelectCollection extends Table {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#js_select' => TRUE,
      '#checkbox_collection' => FALSE,
      '#pre_render' => [
        [$class, 'preRenderTable'],
        [$class, 'preRenderTableselect'],
      ],
      '#process' => [
        [$class, 'processSelectCollection'],
      ],
      '#title' => '',
      '#line_items' => [],
      '#line_options' => [],
      '#empty' => '',
      '#theme' => 'table__select_collection',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return $input === FALSE ? [] : $input;
  }

  /**
   * Creates checkbox or radio elements to populate a tableselect table.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   tableselect element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processSelectCollection(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#header'] = [$element['#title']];

    $element['#sticky'] = FALSE;
    $element['#responsive'] = TRUE;
    $element['#tree'] = TRUE;

    if (count($element['#line_items']) > 0) {
      if (!isset($element['#default_value']) || !is_array($element['#default_value'])) {
        $element['#default_value'] = [];
      }
      if (isset($element['#default_value'][0])) {
        $default_value = $element['#default_value'][0];
      }
      else {
        $default_value = $element['#default_value'];
      }

      // Create a checkbox/select for each item in #line_options in such a way
      // that the value of the tableselect element behaves as if it had been of
      // type checkboxes or select.
      foreach ($element['#line_items'] as $key => $item) {
        // Do not overwrite manually created children.
        if (!isset($element[$key])) {
          // Generate the parents as the autogenerator does, so we will have a
          // unique id for each select/checkbox.
          $key_parents = array_merge($element['#parents'], [0, $key]);
          if ($element['#checkbox_collection']) {
            $element[$key] = [
              '#type' => 'checkbox',
              '#title' => is_array($item) ? HtmlUtility::escape($item['#label']) : $item,
              '#title_display' => 'invisible',
              '#return_value' => $key,
              '#default_value' => isset($default_value[$key]) ? $default_value[$key] : NULL,
              '#attributes' => $element['#attributes'],
              '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
              '#parents' => $key_parents,
              '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $key_parents)),
              '#required' => $element['#required'],
            ];
          }
          else {
            $element[$key] = [
              '#type' => 'select',
              '#options' => $element['#line_options'],
              '#empty_value' => '',
              '#multiple' => FALSE,
              '#default_value' => isset($default_value[$key]) ? $default_value[$key] : NULL,
              '#attributes' => $element['#attributes'],
              '#parents' => $key_parents,
              '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $key_parents)),
              '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
              '#required' => $element['#required'],
            ];
          }
          if (is_array($item) && isset($item['#weight'])) {
            $element[$key]['#weight'] = $item['#weight'];
          }
        }
      }
    }
    else {
      $element['#value'] = [];
    }
    return $element;
  }

  /**
   * Prepares a 'tableselect' #type element for rendering.
   *
   * Adds a column of radio buttons or checkboxes for each row of a table.
   *
   * @param array $element
   *   An associative array containing the properties and children of
   *   the tableselect element. Properties used: #header, #options, #empty,
   *   and #js_select. The #options property is an array of selection options;
   *   each array element of #options is an array of properties. These
   *   properties can include #attributes, which is added to the
   *   table row's HTML attributes; see table.html.twig.
   *   An example of per-row options.
   *
   * @code
   *  $options = array(
   *    array(
   *      'title' => $this->t('How to Learn Drupal'),
   *      'content_type' => $this->t('Article'),
   *      'status' => 'published',
   *      '#attributes' => array('class' => array('article-row')),
   *    ),
   *    array(
   *      'title' => $this->t('Privacy Policy'),
   *      'content_type' => $this->t('Page'),
   *      'status' => 'published',
   *      '#attributes' => array('class' => array('page-row')),
   *    ),
   *  );
   *  $header = array(
   *    'title' => $this->t('Title'),
   *    'content_type' => $this->t('Content type'),
   *    'status' => $this->t('Status'),
   *  );
   *  $form['table'] = array(
   *    '#type' => 'tableselect',
   *    '#header' => $header,
   *    '#options' => $options,
   *    '#empty' => $this->t('No content available.'),
   *  );
   * @endcode
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderTableselect(array $element) {
    $rows = [];
    $header = $element['#header'];
    if (!empty($element['#line_items'])) {
      // Generate a table row for each selectable item in #line_items.
      foreach (Element::children($element) as $key) {
        $row = [];

        $row['data'] = [];
        if (is_array($element['#line_items'][$key])) {
          if (isset($element['#line_items'][$key]['#attributes'])) {
            $row += $element['#line_items'][$key]['#attributes'];
          }
          $row['data'][] = $element['#line_items'][$key]['#label'];
        }
        else {
          $row['data'][] = $element['#line_items'][$key];
        }

        // Render the checkbox / radio element.
        $row['data'][] = [
          'data' => \Drupal::service('renderer')->render($element[$key]),
          'class' => 'webform-select-collection-select',
        ];

        $rows[] = $row;
      }
      // Add an empty header or a "Select all" checkbox to provide room for the
      // checkboxes/radios in the first table column.
      if ($element['#js_select']) {
        if ($element['#checkbox_collection']) {
          // Add a "Select all" checkbox.
          $header[] = ['class' => ['select-all']];
          $element['#attached']['library'][] = 'core/drupal.tableselect';
        }
        else {
          // Add a "Select all" select.
          $header[] = ['class' => ['select-all-collection']];
          $element['#attached']['library'][] = 'webform_select_collection/webform_select_collection';
        }
      }
      else {
        // Add an empty header when radio buttons are displayed or
        // a "Select all" checkbox is not desired.
        $header[] = '';
      }
    }

    $element['#header'] = $header;
    $element['#rows'] = $rows;

    return $element;
  }

}
