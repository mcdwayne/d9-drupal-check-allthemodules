<?php

namespace Drupal\commerce_mautic\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide the settings form for entity clone.
 */
class CommerceMauticSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['commerce_mautic.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_mautic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_mautic.settings');

    $form['order_finished_add_contact'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add mautic contact on order completion'),
      '#description' => $this->t('Adds a new contact in mautic by using billing profile data.'),
      '#default_value' => $config->get('order_finished_add_contact') ? $config->get('order_finished_add_contact') : 0,
    ];

    $form['order_finished_send_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send mail to new contact'),
      '#default_value' => $config->get('order_finished_send_mail') ? $config->get('order_finished_send_mail') : 0,
      '#states' => array(
        'visible' => array(
          ':input[name="order_finished_add_contact"]' => array('checked' => TRUE),
        ),
      ),
    ];

    $form['order_finished_email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mautic email id'),
      '#description' => $this->t('The internal mautic id of the mail that should be sent.'),
      '#default_value' => $config->get('order_finished_email_id'),
      '#states' => array(
        'visible' => array(
          ':input[name="order_finished_add_contact"]' => array('checked' => TRUE),
        ),
      ),
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('commerce_mautic.settings');
    $form_state->cleanValues();

    $add_contact_value = $form_state->getValue('order_finished_add_contact');
    $config->set('order_finished_add_contact', $add_contact_value);
    $send_mail_value = $add_contact_value ? $form_state->getValue('order_finished_send_mail') : 0;
    $config->set('order_finished_send_mail', $send_mail_value);
    $config->set('order_finished_email_id', $form_state->getValue('order_finished_email_id'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
