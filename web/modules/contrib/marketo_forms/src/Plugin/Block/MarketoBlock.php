<?php

/**
 * @file
 * Contains \Drupal\marketo_forms\Plugin\Block\MarketoBlock.
 */

namespace Drupal\marketo_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\marketo_forms\MarketoFormsCore;

/**
 * Display Marketo Form.
 *
 * @Block(
 *   id = "marketo_forms",
 *   admin_label = @Translation("Marketo Form")
 * )
 */
class MarketoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'form_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['form_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Marketo Form'),
      '#description'   => $this->t('Please choose a form you would like to display.'),
      '#default_value' => $this->configuration['form_id'],
      '#required'      => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['form_id'] = $form_state->getValue('form_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('marketo_forms.settings');
    $form_id = $this->configuration['form_id'];
    $host = $config->get('marketo_host_key');
    $api_key = $config->get('marketo_api_key');
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    return [
      '#theme'     => 'marketo_form',
      '#host' => $host,
      '#api_key' => $api_key,
      '#form_id' => $form_id,
      '#locale'    => $langcode,
    ];
  }

}
