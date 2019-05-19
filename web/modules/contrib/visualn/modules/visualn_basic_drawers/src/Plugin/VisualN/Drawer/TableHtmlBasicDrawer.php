<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer;

use Drupal\visualn\Core\DrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Table Html Basic' VisualN drawer.
 *
 * @ingroup drawer_plugins
 *
 * @VisualNDrawer(
 *  id = "visualn_table_html_basic",
 *  label = @Translation("Table Html Basic"),
 *  input = "generic_data_array",
 * )
 */
class TableHtmlBasicDrawer extends DrawerBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Table with variable number of rows and columns');
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'column_labels' => 'data_keys',
      'custom_labels' => '',
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [
      'data_keys' => t('Data keys'),
      'first_row' => t('First row'),
      'custom' => t('Custom'),
    ];

    $form['column_labels'] = [
      '#type' => 'radios',
      '#title' => t('Column labels'),
      '#options' => $options,
      '#default_value' => $this->configuration['column_labels'],
      '#required' => TRUE,
    ];

    // get custom_labels #name property for #states settings
    $element_path = isset($form['#parents']) ? $form['#parents'] : [];
    $element_path[] = 'column_labels';
    $name = array_shift($element_path);
    if (!empty($element_path)) {
      $name .= '[' . implode('][', $element_path) . ']';
    }

    $form['custom_labels'] = [
      '#type' => 'textarea',
      '#title' => t('Custom labels'),
      '#default_value' => $this->configuration['custom_labels'],
      '#description' => t('Enter column labels one per line. If a column label is not set data key is used. Blank lines are ignored.'),
      '#states' => [
        'visible' => [
          ':input[name="' . $name . '"]' => ['value' => 'custom'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {

    $header = [];
    $data = $resource->data;
    $data_keys = [];

    // first row may contain not all keys so check all rows
    if (!empty($data)) {
      foreach ($data as $k => $data_row) {
        $data_keys = array_merge($data_keys, array_keys($data_row));
      }
      $data_keys = array_unique($data_keys);
    }

    switch ($this->configuration['column_labels']) {
      case 'data_keys':
        $header = $data_keys;
        break;
      case 'first_row':
        if (!empty($data)) {
          // And remove the first row
          $first_data_row = array_shift($data);
          foreach ($data_keys as $data_key) {
            $header[] = $first_data_row[$data_key];
          }
        }
        break;
      case 'custom':
        // @todo: what if user wants to substitute just first and third labels?

        // @todo: should custom labels be translated?
        $custom_labels = explode("\n", $this->configuration['custom_labels']);
        foreach ($custom_labels as $k => $custom_label) {
          // @todo: no need to use Html::escape() ?
          $custom_labels[$k] =  trim($custom_label);
          $custom_labels = array_filter($custom_labels);
        }
        $header = $custom_labels;

        // compare custom length and total data keys nubmer
        if (count($custom_labels) < count($data_keys)) {
          // use data keys if custom labels are not enough
          $count_diff = count($custom_labels) - count($data_keys);
          $data_keys_lables = array_slice($data_keys, $count_diff);
          $header = array_merge($header, $data_keys_lables);
        }
        elseif (count($custom_labels) > count($data_keys)) {
          // create empty values array to add to the real values
          $extra_empty_columns = [];
          $count_diff = count($custom_labels) - count($data_keys);
          for ($i = 0; $i < $count_diff; $i++) {
            $extra_empty_columns[] = '';
          }

          //$header = $custom_labels;
        }
        else {
          //$header = $custom_labels;
        }
        break;
    }

    // @todo: see todos from SimpleTableDrawer

    // checking $resource->data since one $data row could be taken for header
    if (!empty($resource->data)) {
      $rows = [];
      //foreach ($resource->data as $k => $data_row) {
      foreach ($data as $k => $data_row) {
        $row = [];
        foreach ($data_keys as $data_key) {
          $row[] = $data_row[$data_key];
        }
        if (isset($extra_empty_columns) && count($extra_empty_columns)) {
          $row = array_merge($row, $extra_empty_columns);
        }
        $rows[] = $row;
      }

      $table = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];
      $build['htmltable_content'] = $table;
    }

    return $resource;
  }

}
