<?php

namespace Drupal\ds_grid\Plugin\DsFieldTemplate;

use Drupal\ds\Plugin\DsFieldTemplate\DsFieldTemplateBase;

/**
 * Plugin for the grid field template.
 *
 * @DsFieldTemplate(
 *   id = "grid",
 *   title = @Translation("Grid"),
 *   theme = "ds_grid",
 * )
 */
class Grid extends DsFieldTemplateBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form) {
    $config = $this->getConfiguration();

    $form['no_cols'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No. Columns'),
      '#size' => '10',
      '#default_value' => $config['no_cols'],
    ];

    $form['label_tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label HTML Tag'),
      '#size' => '10',
      '#default_value' => $config['label_tag'],
    ];

    $form['label_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label CSS Class'),
      '#default_value' => $config['label_class'],
    ];

    $form['item_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grid Item Class'),
      '#default_value' => $config['item_class'],
    ];

    $form['row_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grid Row Class'),
      '#default_value' => $config['row_class'],
    ];

    $form['wrapper_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper Class'),
      '#default_value' => $config['wrapper_class'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [];
    $config['no_cols'] = 3;
    $config['item_class'] = 'col';
    $config['row_class'] = 'row';
    $config['label_tag'] = 'div';
    $config['label_class'] = '';
    $config['wrapper_class'] = '';

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function massageRenderValues(&$field_settings, $values) {
    foreach (array_keys($this->defaultConfiguration()) as $key) {
      if (!empty($values[$key])) {
        $field_settings[$key] = $values[$key];
      }
    }
  }

}
