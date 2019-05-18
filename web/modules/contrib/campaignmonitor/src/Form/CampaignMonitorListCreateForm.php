<?php

namespace Drupal\campaignmonitor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure campaignmonitor settings for this site.
 */
class CampaignMonitorListCreateForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'campaignmonitor_list_create';
  }

  /**
   *
   */
  protected function getEditableConfigNames() {
    return ['campaignmonitor.settings.list'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $list_id = NULL) {
    $form = [];

    $form['listname'] = [
      '#type' => 'textfield',
      '#title' => t('List name'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['UnsubscribePage'] = [
      '#type' => 'textfield',
      '#title' => t('Unsubscribe page'),
      '#default_value' => '',
    ];

    $form['ConfirmationSuccessPage'] = [
      '#type' => 'textfield',
      '#title' => t('Confirmation success page'),
      '#default_value' => '',
    ];

    $form['ConfirmedOptIn'] = [
      '#type' => 'checkbox',
      '#title' => t('Confirmed Opt In'),
      '#default_value' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * Create list validation form handler, which calls the API to create the list.
   * This is done here to ensure better user feedback on failure.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return boolean FALSE on failure
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $options = [
      'listname' => $values['listname'],
      'UnsubscribePage' => $values['UnsubscribePage'],
      'ConfirmationSuccessPage' => $values['ConfirmationSuccessPage'],
      'ConfirmedOptIn' => $values['ConfirmedOptIn'],
    ];
    $result = campaignmonitor_create_list($options);

    if ($result != 'success') {
      $form_state->setErrorByName('', $result);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * List creation submit handler, used to set a positive feedback message and
   * rehash the block table, to ensure that the new list subscribe block is
   * available.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    drupal_set_message(t('List has been created at Campaign monitor.'), 'status');

    parent::submitForm($form, $form_state);
  }

}
