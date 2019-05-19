<?php

namespace Drupal\views_merge_rows\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Provides interface to manage merge options on a per-field basis.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "views_merge_rows",
 *   title = @Translation("Merge rows"),
 *   help = @Translation("Merges rows with the same values in the specified fields."),
 *   no_ui = FALSE
 * )
 */
class ViewsMergeRowsDisplayExtenderPlugin extends DisplayExtenderPluginBase {

  /**
   * Provides a list of options for this plugin.
   */
  public function defineOptionsAlter(&$options) {
    $options['merge_rows'] = ['default' => FALSE, 'bool' => TRUE];
    $options['use_grouping'] = ['default' => FALSE, 'bool' => TRUE];
    $options['field_config'] = ['default' => []];
  }

  /**
   * Returns configuration for row merging.
   *
   * Only returns the configuration for the fields present in the view.
   * If a new field was added to the view, the default configuration for this
   * field is returned.
   *
   * @return array
   *   Configuration for row merging.
   */
  public function getOptions() {
    if ($this->displayHandler->usesFields()) {
      $options = [];
      $options['merge_rows'] = $this->displayHandler->getOption('merge_rows');
      if (empty($options['merge_rows'])) {
        $options['merge_rows'] = FALSE;
      }
      $options['use_grouping'] = $this->displayHandler->getOption('use_grouping');
      if (empty($options['use_grouping'])) {
        $options['use_grouping'] = FALSE;
      }
      $options['field_config'] = [];
      $field_config = $this->displayHandler->getOption('field_config');
      $fields = $this->displayHandler->getOption('fields');
      foreach ($fields as $field => $info) {
        if (isset($field_config[$field])) {
          $options['field_config'][$field] = $field_config[$field];
        }
        else {
          $options['field_config'][$field] = [
            'merge_option' => 'merge_unique',
            'exclude_first' => FALSE,
            'prefix' => '',
            'separator' => ', ',
            'suffix' => '',
          ];
        }
      }
    }
    else {
      $options['merge_rows'] = FALSE;
      $options['use_grouping'] = FALSE;
      $options['field_config'] = [];
    }
    return $options;
  }

  /**
   * Provides the form to set the rows merge options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $this->viewsMergeRowsOptionsFormBuild($form, $form_state);
  }

  /**
   * Handles any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $this->viewsMergeRowsOptionsFormSubmit($form, $form_state);
  }

  /**
   * Provides the default summary for options in the views UI.
   */
  public function optionsSummary(&$categories, &$options) {
    if ($this->displayHandler->usesFields()) {
      $configuration = $this->getOptions();
      $options['views_merge_rows'] = [
        'category' => 'other',
        'title' => t('Merge rows'),
        'value' => $configuration['merge_rows'] ? t('Settings') : t('No'),
        'desc' => t('Allow merging rows with the same content in the specified fields.'),
      ];
    }
  }

  /**
   * Provides a form to edit options for this plugin.
   */
  protected function viewsMergeRowsOptionsFormBuild(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'views_merge_rows') {
      $options = $this->getOptions();

      if ($this->displayHandler->usesPager()) {
        $form['warning_markup'] = [
          '#markup' => '<div class="warning messages">' . t('It is highly recommended to disable pager if you merge rows.') . '</div>',
        ];
      }
      else {
        $form['warning_markup'] = [];
      }
      $form['#tree'] = TRUE;
      $form['#theme'] = 'merge_rows_theme';
      $form['#title'] .= t('Merge rows with the same content.');
      $form['merge_rows'] = [
        '#type' => 'checkbox',
        '#title' => t('Merge rows with the same content in the specified fields'),
        '#default_value' => $options['merge_rows'],
      ];
      $form['use_grouping'] = [
        '#type' => 'checkbox',
        '#title' => t('Merge rows using the grouping defined in the base view'),
        '#default_value' => $options['use_grouping'],
      ];

      // Create an array of allowed columns from the data we know:
      $field_names = $this->displayHandler->getFieldLabels();

      foreach ($field_names as $field => $name) {
        $safe = str_replace(['][', '_', ' '], '-', $field);
        // Markup for the field name.
        $form['field_config'][$field]['name'] = ['#markup' => $name];
        // Select for merge options.
        $form['field_config'][$field]['merge_option'] = [
          '#type' => 'select',
          '#options' => [
            'filter' => t('Use values of this field as a filter'),
            'merge' => t('Merge values of this field'),
            'merge_unique' => t('Merge unique values of this field'),
            'first_value' => t('Use the first value of this field'),
            'highest_value' => t('Use the highest value of this field'),
            'lowest_value' => t('Use the lowest value of this field'),
            'average' => t('Use the average value of this field'),
            'std_deviation' => t('Use the sample standard deviation of this field'),
            'sum' => t('Sum values of this field'),
            'count' => t('Count merged values of this field'),
            'count_unique' => t('Count merged unique values of this field'),
            'count_minus_count_unique' => t('Calculate the number of merged values minus the number of merged unique values of this field'),
          ],
          '#default_value' => $options['field_config'][$field]['merge_option'],
        ];

        $form['field_config'][$field]['exclude_first'] = [
          '#title' => '',
          '#type' => 'checkbox',
          '#default_value' => $options['field_config'][$field]['exclude_first'],
        ];

        $form['field_config'][$field]['prefix'] = [
          '#id' => 'views-merge-rows-prefix',
          '#title' => '',
          '#type' => 'textfield',
          '#size' => 10,
          '#default_value' => $options['field_config'][$field]['prefix'],
          '#dependency' => ['edit-options-field-config-' . $safe . '-merge-option' => ['merge', 'merge_unique']],
        ];

        $form['field_config'][$field]['separator'] = [
          '#id' => 'views-merge-rows-separator',
          '#title' => '',
          '#type' => 'textfield',
          '#size' => 10,
          '#default_value' => $options['field_config'][$field]['separator'],
          '#dependency' => ['edit-options-field-config-' . $safe . '-merge-option' => ['merge', 'merge_unique']],
        ];

        $form['field_config'][$field]['suffix'] = [
          '#id' => 'views-merge-rows-suffix',
          '#title' => '',
          '#type' => 'textfield',
          '#size' => 10,
          '#default_value' => $options['field_config'][$field]['suffix'],
          '#dependency' => ['edit-options-field-config-' . $safe . '-merge-option' => ['merge', 'merge_unique']],
        ];
      }
    }
  }

  /**
   * Saves the row merge options.
   */
  protected function viewsMergeRowsOptionsFormSubmit(&$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('options') as $option => $value) {
      $this->displayHandler->setOption($option, $value);
    }
  }

}
