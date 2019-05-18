<?php


namespace Drupal\chatroom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure chatroom settings.
 */
class ChatroomSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chatroom_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'chatroom.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('chatroom.settings');

    $form['msg_date_format'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Message date format'),
      '#default_value' => $config->get('msg_date_format'),
      '#description' => $this->t('The date format to use in chatrooms.'),
      '#required' => TRUE,
    );

    $form['command_prefix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Command prefix'),
      '#default_value' => $config->get('command_prefix'),
      '#description' => $this->t('Messages beginning with the characters specified here will be treated as commands.'),
      '#required' => TRUE,
    );

    $form['allow_anon_name'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow anonymous names'),
      '#default_value' => $config->get('allow_anon_name'),
      '#description' => $this->t('Allow anonymous users to specify their names when they post to a chatroom.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('chatroom.settings')
      ->set('msg_date_format', $form_state->getValue('msg_date_format'))
      ->set('command_prefix', $form_state->getValue('command_prefix'))
      ->set('allow_anon_name', $form_state->getValue('allow_anon_name'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
