<?php

namespace Drupal\views_row_insert\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * A Views style that renders markup for Bootstrap tabs.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "row_insert",
 *   title = @Translation("Row Insert"),
 *   help = @Translation("Views Row Insert display plugin."),
 *   theme = "views_row_insert",
 *   display_types = {"normal"}
 * )
 */
class ViewsRowInsert extends StylePluginBase {

  /**
   * Does this Style plugin allow Row plugins?
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the Style plugin support grouping of rows?
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['use_plugin'] = ['default' => TRUE];
    $options['data_mode'] = ['default' => 'vri_text'];
    $options['block_name'] = ['default' => FALSE];
    $options['custom_row_data'] = ['default' => '<strong>Your HTML is here</strong>'];
    $options['rows_number'] = ['default' => 2];
    $options['show_once'] = ['default' => FALSE];
    $options['row_class'] = ['default' => FALSE];
    $options['class_name'] = ['default' => FALSE];
    $options['default_rows'] = ['default' => FALSE];
    $options['strip_rows'] = ['default' => FALSE];
    $options['row_header'] = ['default' => FALSE];
    $options['row_footer'] = ['default' => FALSE];
    $options['row_limit_flag'] = ['default' => FALSE];
    $options['row_limit'] = ['default' => '0'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['use_plugin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use this plugin'),
      '#default_value' => $this->options['use_plugin'],
      '#description' => $this->t('Check if you want to use this plugin.'),
    ];
    $form['data_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Row type'),
      '#options' => ['vri_block' => 'Block', 'vri_text' => 'Custom content'],
      '#default_value' => $this->options['data_mode'],
      '#after_build' => ['_views_row_insert_process_radios'],
    ];
    $form['block_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a block'),
      '#options' => _views_row_insert_get_blocks(),
      '#default_value' => $this->options['block_name'],
      '#description' => $this->t('Select a block to insert instead of using custom content.'),
    ];
    $form['custom_row_data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom content'),
      '#default_value' => $this->options['custom_row_data'],
      '#description' => $this->t('Enter text or html code. Be careful, this field is unrestricted!'),
      '#attributes' => ['class' => ['custom-row-data']],
    ];
    $form['rows_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Insert after every Nth row'),
      '#min' => 1,
      '#default_value' => $this->options['rows_number'],
      '#description' => $this->t('Number of rows to skip.'),
    ];
    $form['row_header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Start with inserted row'),
      '#default_value' => $this->options['row_header'],
      '#description' => $this->t('Check if you want to insert the row at the beginning of your output.'),
    ];
    $form['row_footer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Insert row at the bottom'),
      '#default_value' => $this->options['row_footer'],
      '#description' => $this->t('If possible, the row will be added at the bottom of your output.'),
    ];
    $form['row_limit_flag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit the amount of inserted rows'),
      '#default_value' => $this->options['row_limit_flag'],
      '#description' => $this->t('Check if you want to specify the amount of inserted rows.'),
    ];
    $form['row_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('How many times to display the row?'),
      '#min' => 0,
      '#default_value' => $this->options['row_limit'],
      '#description' => $this->t('Set display limit number of inserted rows per page, 0 - no limitations.'),
    ];
    $form['class_name'] = [
      '#title' => $this->t('Insert row class name(s)'),
      '#type' => 'textfield',
      '#default_value' => $this->options['class_name'],
      '#description' => $this->t('If specified, the inserted row will be wrapped by div element with above classes.'),
    ];
    $form['row_class'] = [
      '#title' => $this->t('Row class'),
      '#type' => 'textfield',
      '#default_value' => $this->options['row_class'],
      '#description' => $this->t('The class to provide on each original row from the view output.'),
    ];
    $form['default_rows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add views row classes'),
      '#default_value' => $this->options['default_rows'],
      '#description' => $this->t('Add the default row classes like views-row, views-row-1 to the output. You can use this to quickly reduce the amount of markup the view provides by default, at the cost of making it more difficult to apply CSS.'),
    ];
    $form['strip_rows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add striping (odd/even), first/last row classes'),
      '#default_value' => $this->options['strip_rows'],
      '#description' => $this->t('Add css classes to the first and last line, as well as odd/even classes for striping.'),
    ];
    $form['#attached']['library'][] = 'views_row_insert/vri-plugin-admin';
  }

}
