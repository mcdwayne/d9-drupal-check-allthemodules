<?php

namespace Drupal\digitallocker_requester\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DigitallockerRequesterSettings.
 *
 * @package Drupal\digitallocker_requester\Form
 */
class DigitallockerRequesterSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'digitallocker_requester_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('digitallocker_requester.settings');

    $form['requester_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Requester Id'),
      '#description' => $this->t('Set the Requester Id provided by Digital Locker.'),
      '#default_value' => $config->get('requester_id'),
    ];

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#description' => $this->t('Set the Secret Key provided by Digital Locker.'),
      '#default_value' => $config->get('secret_key'),
    ];

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base Url'),
      '#description' => $this->t('Set the Base Url provided by Digital Locker.'),
      '#default_value' => $config->get('base_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    (\Drupal::service('config.factory'))
      ->getEditable('digitallocker_requester.settings')
      ->set('requester_id', $form_state->getValue('requester_id'))
      ->set('secret_key', $form_state->getValue('secret_key'))
      ->set('base_url', $form_state->getValue('base_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['digitallocker_requester.settings'];
  }

}
