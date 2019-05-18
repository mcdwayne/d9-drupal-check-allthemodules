<?php

/**
 * @file
 *
 * Contains \Drupal\cnzz\Plugin\Block\CnzzStatisticsDetailBlock
 */

namespace Drupal\cnzz\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;

/**
 * This block is allow user paste cnzz statistics detail code here.
 *
 * @Block(
 *   id = "cnzz_statistics_detail",
 *   admin_label = @Translation("CNZZ statistics detail"),
 *   category = @Translation("CNZZ")
 * )
 */
class CnzzStatisticsDetailBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->configuration['cnzz_statistics_detail_code'],
      '#allowed_tags' => ['script'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $render_cnzz = cnzz_get_render_link();

    $form['cnzz_statistics_detail_code'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('CNZZ statistics detail code'),
      '#description' => $this->t('Please paste the corresponding code from @cnzz_link site here', array('@cnzz_link' => render($render_cnzz))),
      '#default_value' => $this->configuration['cnzz_statistics_detail_code'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['cnzz_statistics_detail_code'] = $form_state->getValue('cnzz_statistics_detail_code');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'cnzz_statistics_detail_code' => '',
    );
  }
}
