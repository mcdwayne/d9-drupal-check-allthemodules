<?php

namespace Drupal\sender\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sender_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['sender.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('sender.settings');

    $form['header'] = [
      '#markup' => t('Configure the Sender module on this page.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    // Queue settings.
    $form['queue'] = [
      '#type' => 'fieldset',
      '#title' => t('Messages queue'),
    ];
    $form['queue']['queue_on'] = [
      '#type' => 'checkbox',
      '#title' => t('Enqueue messages'),
      '#description' => t('Enqueue messages and send them on cron run.'),
      '#default_value' => $config->get('queue_on'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sender.settings')
      ->set('queue_on', $form_state->getValue('queue_on'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
