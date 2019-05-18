<?php

namespace Drupal\exchange_rate_cr\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block.
 *
 * @Block(
 *   id = "exchangeratecr_block",
 *   admin_label = @Translation("Exchange Rate"),
 *   description = @Translation("The converted amounts are estimates due to currency variability"),
 * )
 */
class ExchangeratecrBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Return the form @ Form/ExchangeratecrBlockForm.php.
    return \Drupal::formBuilder()->getForm('Drupal\exchange_rate_cr\Form\ExchangeratecrBlockForm');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('exchange_rate_cr_block_settings', $form_state->getValue('exchangeratecr_block_settings'));
  }

}
