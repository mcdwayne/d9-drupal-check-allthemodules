<?php

namespace Drupal\bootstrap_utilities\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Add Bootstrap class to any tables.
 *
 * @Filter(
 *   id = "bootstrap_utilities_table_filter",
 *   title = @Translation("Bootstrap Utilities - Table Classes"),
 *   description = @Translation("This filter allows you to add default Bootstrap classes to a table, controlled by settings"),
 *   settings = {
 *     "table_remove_width_height" = TRUE,
 *     "table_row_striping" = FALSE,
 *     "table_bordered" = FALSE,
 *     "table_row_hover" = FALSE,
 *     "table_small" = FALSE,
 *   },
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class TableFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['table_remove_width_height'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove <code>width</code> and <code>height</code> attributes from table cells.'),
      '#default_value' => $this->settings['table_remove_width_height'],
    ];
    $form['table_row_striping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Striped rows'),
      '#default_value' => $this->settings['table_row_striping'],
      '#description' => $this->t('Adds <code>.table-striped</code> to add zebra-striping to any table row within the <code>&lt;tbody&gt;</code>.'),
    ];
    $form['table_bordered'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bordered table'),
      '#default_value' => $this->settings['table_bordered'],
      '#description' => $this->t('Adds <code>.table-bordered</code> for borders on all sides of the table and cells.'),
    ];
    $form['table_row_hover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hoverable rows'),
      '#default_value' => $this->settings['table_row_hover'],
      '#description' => $this->t('Adds <code>.table-hover</code> to enable a hover state on table rows within a <code>&lt;tbody&gt;</code>.'),
    ];
    $form['table_small'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Small table'),
      '#default_value' => $this->settings['table_small'],
      '#description' => $this->t('Adds <code>.table-sm</code> to make tables more compact by cutting cell padding in half.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'table') !== FALSE) {
      $setting_classes = [];
      $setting_classes[] = 'table';

      if ($this->settings['table_row_striping']) {
        $setting_classes[] = 'table-striped';
      }
      if ($this->settings['table_bordered']) {
        $setting_classes[] = 'table-bordered';
      }
      if ($this->settings['table_row_hover']) {
        $setting_classes[] = 'table-hover';
      }
      if ($this->settings['table_small']) {
        $setting_classes[] = 'table-sm';
      }

      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      $table_elements = $xpath->query('//table');
      if (!is_null($table_elements)) {
        foreach ($table_elements as $element) {
          if ($element->getAttribute('class')) {
            $setting_classes[] = $element->getAttribute('class');
          }
          $all_classes = implode(' ', $setting_classes);
          $element->setAttribute('class', $all_classes);
        }
      }

      if ($this->settings['table_remove_width_height']) {
        $tbody_elements = $xpath->query('//tbody');
        if (!is_null($tbody_elements)) {
          foreach ($tbody_elements as $element) {
            $element->removeAttribute(['width', 'height']);
          }
        }
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
