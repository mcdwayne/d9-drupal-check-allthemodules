<?php

namespace Drupal\hipchat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class HipChatSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hipchat_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hipchat_settings.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $hipchat_config = $this->config('hipchat_settings.config');

    $admin_page_link = Link::fromTextAndUrl(t('your HipChat admin page'), Url::fromUri('https://www.hipchat.com/group_admin/api'))->toString()->getGeneratedLink();

    $form['hipchat_token'] = array(
      '#type' => 'textfield',
      '#title' => t('HipChat token'),
      '#description' => t('Get an Admin API token from !hipchat_link', array('!hipchat_link' => $admin_page_link)),
      '#default_value' => $hipchat_config->get('hipchat_token'),
    );
    $form['hipchat_default_room'] = array(
      '#type' => 'textfield',
      '#title' => t('HipChat default room'),
      '#description' => t('Enter the default room to send notices. Enter the human name, not the room id'),
      '#default_value' => $hipchat_config->get('hipchat_default_room'),
    );

    $form['hipchat_content_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Only send notifications for these content types'),
      '#default_value' => $hipchat_config->get('hipchat_content_types'),
      '#options' => node_type_get_names(),
      '#description' => t('All types will be included if none are selected.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('hipchat_settings.config')
      ->set('hipchat_token', $form_state->getValue('hipchat_token'))
      ->set('hipchat_default_room', $form_state->getValue('hipchat_default_room'))
      ->set('hipchat_content_types', $form_state->getValue('hipchat_content_types'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}