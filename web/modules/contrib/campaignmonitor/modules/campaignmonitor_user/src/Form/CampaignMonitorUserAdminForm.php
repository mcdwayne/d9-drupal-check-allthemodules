<?php

namespace Drupal\campaignmonitor_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure campaignmonitor settings for this site.
 */
class CampaignMonitorUserAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'campaignmonitor_user_admin_settings';
  }

  /**
   *
   */
  protected function getEditableConfigNames() {
    return ['campaignmonitor_user.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('campaignmonitor_user.settings');

    $form['subscription_heading'] = [
      '#type' => 'textfield',
      '#title' => t('Subscription Heading'),
      '#description' => t('The heading of the page'),
      '#default_value' => $config->get('subscription_heading'),
    ];

    $form['subscription_text'] = [
      '#type' => 'textarea',
      '#title' => t('List Heading'),
      '#description' => t('The text below the Subscription heading'),
      '#default_value' => $config->get('subscription_text'),
    ];

    $form['list_heading'] = [
      '#type' => 'textarea',
      '#title' => t('List Heading'),
      '#description' => t('The heading above the lists on the subscription form'),
      '#default_value' => $config->get('list_heading'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('campaignmonitor_user.settings');
    $config
      ->set('subscription_heading', $form_state->getValue('subscription_heading'))
      ->set('subscription_text', $form_state->getValue('subscription_text'))
      ->set('list_heading', $form_state->getValue('list_heading'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
