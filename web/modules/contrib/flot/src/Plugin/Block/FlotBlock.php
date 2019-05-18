<?php

namespace Drupal\flot\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Flot Block.
 *
 * @Block(
 *   id = "flot_block",
 *   admin_label = @Translation("Flot Block"),
 *   category = @Translation("Charting"),
 * )
 */
class FlotBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    //ToDo:
    //Add custom element id.

    $settings = $this->getConfiguration();
    $data = $settings['flot_block_settings']['data'];
    $series = [];
    foreach ($data as $series_data) {
      $series[] = ['data' => json_decode($series_data)];
    }
    $options = []; 
    if ($settings['flot_block_type'] == 1) {
      $options = ['bars' => ['show' => TRUE]];
    }
    $output['flot'] = [
      '#type' => 'flot',
      '#data' => $series,
      '#options' => $options,
    ];
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    //Selectbox for input type (JSON or Array)
    //TextBox for data input
    $config = $this->getConfiguration();
    $data = isset($config['flot_block_settings']['data']) ? $config['flot_block_settings']['data'] : [];
    $text = "";
    $first = true;
    foreach ($data as $line) {
      if (!$first) {
        $text .= "\n";
      } else {
        $first = false;
      }
      $text .= $line;
    }
    $form['flot_block_type'] = [
      '#type' => 'select',
      '#title' => 'Chart Type',
      '#options' => ['Lines', 'Bars', 'Pie'],
      '#default_value' => isset($config['flot_block_type']) ? $config['flot_block_type'] : 0,
    ];
      $form['flot_block_settings'] = [
	      '#type' => 'textarea',
	      '#title' => $this->t('Flot Data'),
	      '#default_value' => $text,
      ];

      return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $textarea_value = $form_state->getValue('flot_block_settings');
    $settings = [];
    $settings['label'] = $form_state->getValue('label');
    $settings['flot_block_type'] = $form_state->getValue('flot_block_type');
    $line_array = explode("\n", $textarea_value);
    foreach($line_array as $line) {
      $settings['flot_block_settings']['data'][] = $line;
    }
    $this->configuration = $settings;
    
  }
}

