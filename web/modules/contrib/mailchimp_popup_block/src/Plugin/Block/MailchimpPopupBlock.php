<?php

namespace Drupal\mailchimp_popup_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Creates a Mailchimp Popup Block.
 *
 * @Block(
 *  id = "mailchimp_popup_block",
 *  admin_label = @Translation("Mailchimp Popup Block"),
 * )
 */
class MailchimpPopupBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    return [
      '#theme' => 'mailchimp_popup',
      '#description' => $config['description'],
      '#button_text' => $config['button_text'],
      '#mailchimp_baseurl' => $config['mailchimp_baseurl'],
      '#mailchimp_uuid' => $config['mailchimp_uuid'],
      '#mailchimp_lid' => $config['mailchimp_lid'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['mailchimp'] = [
      '#type' => 'details',
      '#title' => $this->t('Mailchimp configuration.'),
    ];
    $form['mailchimp']['mailchimp_baseurl'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mailchimp Base URL'),
      '#description' => $this->t('The mailchimp base URL. Have a look into your mailchimp account popup form generation.'),
      '#default_value' => isset($config['mailchimp_baseurl']) ? $config['mailchimp_baseurl'] : NULL,
    ];
    $form['mailchimp']['mailchimp_uuid'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mailchimp UUID'),
      '#description' => $this->t('The mailchimp UUID.'),
      '#default_value' => isset($config['mailchimp_uuid']) ? $config['mailchimp_uuid'] : NULL,
    ];
    $form['mailchimp']['mailchimp_lid'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mailchimp List ID'),
      '#description' => $this->t('The mailchimp list id.'),
      '#default_value' => isset($config['mailchimp_lid']) ? $config['mailchimp_lid'] : NULL,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Intro text for the newsletter, shown before the button.'),
      '#default_value' => isset($config['description']) ? $config['description'] : NULL,
    ];
    $form['button_text'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Button text'),
      '#description' => $this->t('The text of the popup trigger button.'),
      '#default_value' => isset($config['button_text']) ? $config['button_text'] : NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $this->setConfigurationValue('mailchimp_baseurl', $form_state->getValue([
        'mailchimp',
        'mailchimp_baseurl',
      ]));
      $this->setConfigurationValue('mailchimp_uuid', $form_state->getValue([
        'mailchimp',
        'mailchimp_uuid',
      ]));
      $this->setConfigurationValue('mailchimp_lid', $form_state->getValue([
        'mailchimp',
        'mailchimp_lid',
      ]));
      $this->setConfigurationValue('description', $form_state->getValue('description'));
      $this->setConfigurationValue('button_text', $form_state->getValue('button_text'));
    }
  }

}
