<?php

namespace Drupal\marketo_subscribe\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\marketo_subscribe\Form\MarketoSubscribeForm;

/**
 * Provides a block with a single e-mail field and sends the data to Marketo.
 *
 * @Block(
 *   id = "marketo_subscribe_block",
 *   admin_label = @Translation("Marketo Subscribe block"),
 * )
 */
class MarketoSubscribeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'list_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['marketo_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Marketo settings'),
      '#description' => $this->t('Specify the settings of this Marketo block.'),
    ];
    $form['marketo_settings']['list_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('List ID'),
      '#description' => $this->t('The List Id can be obtained from the
      URL of the list in the UI, where the URL will resemble
      https://app-***.marketo.com/#ST1001A1. In this URL, the id is 1001, it
      will always be between the first set of letters in the URL and the second
      set of letters.'),
      '#default_value' => $this->configuration['list_id'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $marketo_settings = $form_state->getValue('marketo_settings');
    $this->configuration['list_id'] = $marketo_settings['list_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = new MarketoSubscribeForm(\Drupal::service('marketo_ma.api_client'));
    $form->setListId($this->configuration['list_id']);

    return \Drupal::formBuilder()->getForm($form);
  }

}
