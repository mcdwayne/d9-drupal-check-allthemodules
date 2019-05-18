<?php

namespace Drupal\friendship\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure friendship settings form.
 */
class FriendshipSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'friendship_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'friendship.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('friendship.settings');

    $form['button_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Button settings'),
    ];

    $form['button_settings']['follow_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Follow text'),
      '#default_value' => $config->get('button.follow_text'),
    ];

    $form['button_settings']['unfollow_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unfollow text'),
      '#default_value' => $config->get('button.unfollow_text'),
    ];

    $form['button_settings']['accept_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accept text'),
      '#default_value' => $config->get('button.accept_text'),
    ];

    $form['button_settings']['remove_friend_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remove friend text'),
      '#default_value' => $config->get('button.remove_friend_text'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('friendship.settings')
      ->set('button.follow_text', $form_state->getValue('follow_button_text'))
      ->set('button.unfollow_text', $form_state->getValue('unfollow_button_text'))
      ->set('button.accept_text', $form_state->getValue('accept_button_text'))
      ->set('button.remove_friend_text', $form_state->getValue('remove_friend_button_text'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
