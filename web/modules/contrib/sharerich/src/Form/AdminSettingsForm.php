<?php

/**
 * @file
 * Contains \Drupal\sharerich\Form\AdminSettingsForm.
 */

namespace Drupal\sharerich\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;

/**
 * Class AdminSettingsForm.
 *
 * @package Drupal\sharerich\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sharerich.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sharerich.settings');

    $form['global'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Global settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['global']['allowed_html'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Allowed HTML tags'),
      '#description' => $this->t('A list of HTML tags that can be used'),
      '#default_value' => $config->get('allowed_html'),
    );

    $form['social'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Social networks'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['social']['facebook_app_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Facebook App ID'),
      '#description' => $this->t('You need to have an App ID, which you can get from Facebook.'),
      '#default_value' => $config->get('facebook_app_id'),
    );
    $form['social']['facebook_site_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Site URL'),
      '#description' => $this->t('You need to have an App ID, which you can get from Facebook.'),
      '#default_value' => $config->get('facebook_site_url'),
    );
    $form['social']['youtube_username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('YouTube Username'),
      '#description' => $this->t('Enter your YouTube username in order for the social button to link to your YouTube channel.'),
      '#default_value' => $config->get('youtube_username'),
    );
    $form['social']['github_username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Github Username'),
      '#description' => $this->t('Enter your Github username in order for the social button to link to your Github profile.'),
      '#default_value' => $config->get('github_username'),
    );
    $form['social']['instagram_username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Instagram Username'),
      '#description' => $this->t('Enter your Instagram username in order for the social button to link to your Instagram profile.'),
      '#default_value' => $config->get('instagram_username'),
    );
    $form['social']['twitter_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Twitter user'),
      '#description' => $this->t('Used when sharing on twitter to identify the person sharing i.e. via @userid.'),
      '#default_value' => $config->get('twitter_user'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sharerich.settings')
      ->set('allowed_html', $form_state->getValue('allowed_html'))
      ->set('facebook_app_id', $form_state->getValue('facebook_app_id'))
      ->set('facebook_site_url', $form_state->getValue('facebook_site_url'))
      ->set('youtube_username', $form_state->getValue('youtube_username'))
      ->set('instagram_username', $form_state->getValue('instagram_username'))
      ->set('github_username', $form_state->getValue('github_username'))
      ->set('twitter_user', $form_state->getValue('twitter_user'))
      ->save();

    // Clear block cache.
    Cache::invalidateTags(array('block_view'));
  }

}
