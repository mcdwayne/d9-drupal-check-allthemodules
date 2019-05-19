<?php

namespace Drupal\views_rows_wrapper\Plugin\views\style;

use Drupal\views_rows_wrapper\ViewsRowsWrapperTypes;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * A Views style that renders markup for Bootstrap tabs.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "rows_wrapper",
 *   title = @Translation("Rows Wrapper"),
 *   help = @Translation("Views Rows Wrapper display plugin."),
 *   theme = "views_rows_wrapper",
 *   display_types = {"normal"}
 * )
 */
class ViewsRowsWrapper extends StylePluginBase {

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
    $options['use_wrapper'] = ['default' => TRUE];
    $options['element_type'] = ['default' => 0];
    $options['element_types'] = ['default' => ViewsRowsWrapperTypes::elementTypes()];
    $options['attribute_type'] = ['default' => 0];
    $options['attribute_types'] = ['default' => ViewsRowsWrapperTypes::attributeTypes()];
    $options['attribute_name'] = ['default' => ''];
    $options['rows_number'] = ['default' => 2];
    $options['wrap_method'] = ['default' => 0];
    $options['default_rows'] = ['default' => FALSE];
    $options['strip_rows'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['use_wrapper'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use this row wrapper'),
      '#default_value' => $this->options['use_wrapper'],
      '#description' => $this->t('Check if you want to use this plugin.'),
    ];
    $form['element_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Element type'),
      '#options' => $this->options['element_types'],
      '#default_value' => $this->options['element_type'],
      '#description' => $this->t('Select element type.'),
    ];
    $form['attribute_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Attribute type'),
      '#options' => $this->options['attribute_types'],
      '#default_value' => $this->options['attribute_type'],
      '#description' => $this->t('Select attribute type.'),
    ];
    $form['attribute_name'] = [
      '#title' => $this->t('Class/ID attribute name(s)'),
      '#type' => 'textfield',
      '#default_value' => $this->options['attribute_name'],
    ];
    $form['rows_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of rows to wrap'),
      '#min' => 1,
      '#default_value' => $this->options['rows_number'],
      '#description' => $this->t('Choose the number of rows to be wrapped by selected element.'),
    ];
    $form['wrap_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Wrap method'),
      '#default_value' => $this->options['wrap_method'],
      '#options' => [
        0 => $this->t('Apply to all items'),
        1 => $this->t('Wrap once (first rows only)'),
      ],
      '#description' => $this->t('Select the method of how you want to wrap your view results.'),
    ];
    $form['default_rows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add views row classes'),
      '#default_value' => $this->options['default_rows'],
      '#description' => $this->t('Add the default row classes like views-row-1 to the output. You can use this to quickly reduce the amount of markup the view provides by default, at the cost of making it more difficult to apply CSS.'),
    ];
    $form['strip_rows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add striping (odd/even), first/last row classes'),
      '#default_value' => $this->options['strip_rows'],
      '#description' => $this->t('Add css classes to the first and last line, as well as odd/even classes for striping.'),
    ];
  }

}
