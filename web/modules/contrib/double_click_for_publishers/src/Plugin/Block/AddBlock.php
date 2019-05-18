<?php

namespace Drupal\double_click_for_publishers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'DFP Add' block.
 *
 * @Block(
 *  id = "dfp_add_block",
 *  admin_label = @Translation("DFP Add")
 * )
 */
class AddBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $build = [];
    $build['#attached']['library'][] = 'double_click_for_publishers/gpt.library';
    $build['#attached']['drupalSettings']['targeted_add_unit'] = $config['targeted_add_unit'];
    $build['#attached']['drupalSettings']['network_code'] = $config['network_code'];
    $build['#attached']['drupalSettings']['slot_matching_string'] = $config['slot_matching_string'];
    $build['#attached']['drupalSettings']['width'] = $config['width'];
    $build['#attached']['drupalSettings']['height'] = $config['height'];
    $build['add_block']['#markup'] = "<div class ='dfp-block' id='" . $config['slot_matching_string'] . "' style='height:" . $config['height'] . "px; width:" . $config['width'] . "px;'></div>";
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['targeted_add_unit'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Target add unit'),
      '#default_value' => isset($config['targeted_add_unit']) ? $config['targeted_add_unit'] : '',
    ];

    $form['network_code'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 20,
      '#title' => $this->t('Network code'),
      '#default_value' => isset($config['network_code']) ? $config['network_code'] : '',
    ];

    $form['slot_matching_string'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 50,
      '#title' => $this->t('Slot matching string'),
      '#default_value' => isset($config['slot_matching_string']) ? $config['slot_matching_string'] : '',
    ];

    $form['width'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 20,
      '#title' => $this->t('Width'),
      '#default_value' => isset($config['width']) ? $config['width'] : '',
    ];

    $form['height'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 20,
      '#title' => $this->t('Height'),
      '#default_value' => isset($config['height']) ? $config['height'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['targeted_add_unit'] = $values['targeted_add_unit'];
    $this->configuration['network_code'] = $values['network_code'];
    $this->configuration['slot_matching_string'] = $values['slot_matching_string'];
    $this->configuration['width'] = $values['width'];
    $this->configuration['height'] = $values['height'];
  }

}
