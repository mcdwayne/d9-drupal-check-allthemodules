<?php

namespace Drupal\views_math\Plugin\views\area;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\views\Plugin\views\style\DefaultSummary;

/**
 * Views area handler to display total value for the field.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("views_math_area_total")
 */
class Total extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['content'] = [
      'default' => $this->t('Total sum: @total'),
    ];

    $options['total_field'] = [
      'default' => '',
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $supported_fields = ['number_decimal'];
    $field_options = [
      '' => t('- Select the field -'),
    ];
    $fields = $this->view->displayHandlers->get($this->view->current_display)->getOption('fields');
    foreach ($fields as $field_name => $field) {
      if (in_array($field['type'], $supported_fields)) {
        $field_options[$field['table'].'.'.$field_name] = $field['label'] . ' (' . $field['type'] . ')';
      }
    }
    $form['total_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#required' => TRUE,
      '#options' => $field_options,
      '#default_value' => $this->options['total_field'],
    ];

    $item_list = [
      '#theme' => 'item_list',
      '#items' => [
        '@total -- the total sum for the field',
      ],
    ];
    $list = \Drupal::service('renderer')->render($item_list);
    $form['content'] = [
      '#title' => $this->t('Display'),
      '#type' => 'textarea',
      '#rows' => 3,
      '#default_value' => $this->options['content'],
      '#description' => $this->t('You may use HTML code in this field. The following tokens are supported:') . $list,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    // Must have options and does not work on summaries.
    if (!isset($this->options['content']) || !isset($this->options['total_field']) || $this->view->style_plugin instanceof DefaultSummary) {
      return [];
    }

    $query = $this->view->build_info['query'];
    // Remove range if any.
    $query->range(NULL, NULL);
    // Add a field to calculate the total.
    $query->addField(NULL, $this->options['total_field'], $this->options['field']);
    // Calculate that field index.
    $fields = $query->getFields();
    $field_index = array_search($this->options['field'], array_keys($fields));
    $result = $query->execute();
    $array_items = $result->fetchCol($field_index);
    $total = array_sum($array_items);

    $output = '';
    $format = $this->options['content'];
    // Get the search information.
    $replacements = [];
    $replacements['@total'] = $total;
    // Send the output.
    if (!empty($total) || !empty($this->options['empty'])) {
      $output .= Xss::filterAdmin(str_replace(array_keys($replacements), array_values($replacements), $format));
      // Return as render array.
      return [
        '#markup' => $output,
      ];
    }

    return [];
  }

}
