<?php

namespace Drupal\simple_adsense\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'SimpleAdSenseBlock' block.
 *
 * @Block(
 *  id = "simple_adsense_block",
 *  admin_label = @Translation("Simple adsense block"),
 * )
 */
class SimpleAdSenseBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['slot'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ad Unit Id'),
      '#description' => $this->t('Unique Responsive Ad Unit Id. eg: 5556618763'),
      '#default_value' => isset($this->configuration['slot']) ? $this->configuration['slot'] : '',
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '10',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['slot'] = $form_state->getValue('slot');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('simple_adsense.settings');

    $build = [];
    $build[] = array(
      '#theme' => 'simple_adsense',
      '#publisher_id' => 'ca-' . $config->get('publisher_id'),
      '#slot' => $this->configuration['slot'],
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'slot' => '5556618763',
    ];
  }

}
