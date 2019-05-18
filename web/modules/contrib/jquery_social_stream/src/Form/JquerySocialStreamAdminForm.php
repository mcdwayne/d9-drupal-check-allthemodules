<?php

/**
 * @file
 * Contains \Drupal\jquery_social_stream\Form\JquerySocialStreamAdminForm.
 */

namespace Drupal\jquery_social_stream\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class JquerySocialStreamAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jquery_social_stream_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $config = $this->config('jquery_social_stream.settings');

    // Twitter.
    $form['twitter'] = array(
      '#type' => 'details',
      '#tree' => FALSE,
      '#title' => t('Twitter settings'),
      '#open' => TRUE,
    );
    $form['twitter']['twitter_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter API Key'),
      '#default_value' => $config->get('twitter_api_key'),
    );
    $form['twitter']['twitter_api_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter API Secret'),
      '#default_value' => $config->get('twitter_api_secret'),
    );
    $form['twitter']['twitter_access_token'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter Access Token'),
      '#default_value' => $config->get('twitter_access_token'),
    );
    $form['twitter']['twitter_access_token_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter Access Token Secret'),
      '#default_value' => $config->get('twitter_access_token_secret'),
    );

    // Google +.
    $form['google'] = array(
      '#type' => 'details',
      '#tree' => FALSE,
      '#title' => t('Google +1 settings'),
      '#open' => TRUE,
    );
    $form['google']['google_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Google +1 API Key'),
      '#default_value' => $config->get('google_key'),
    );

    // Instagram.
    $form['instagram'] = array(
      '#type' => 'details',
      '#tree' => FALSE,
      '#title' => t('Instagram settings'),
      '#open' => TRUE,
    );

    $form['instagram']['instagram_access_token'] = array(
      '#type' => 'textfield',
      '#title' => t('Instagram Access Token'),
      '#description' => t('Access token created from the authorisation of your OAuth Client'),
      '#default_value' => $config->get('instagram_access_token', ''),
    );
    $form['instagram']['instagram_redirect_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Instagram Redirect URL'),
      '#description' => t('The URL entered as the redirect URL when registering your new OAuth Client in the Instagram API setup'),
      '#default_value' => $config->get('instagram_redirect_url', ''),
    );
    $form['instagram']['instagram_client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Instagram Client ID'),
      '#description' => t('Client ID for API created after registering your new OAuth Client in the instagram API setup'),
      '#default_value' => $config->get('instagram_client_id', ''),
    );

    $doc_file = libraries_get_path('jquery-social-stream') . '/index.html';
    if (file_exists(DRUPAL_ROOT . '/' . $doc_file)) {
      $form['google']['#description'] = t('See section <em>Creating Your Own Google API Key</em> in <a href="/!path">jQuery Social Stream plugin documentation</a> for details.', array('!path' => $doc_file));
      $form['instagram']['#description'] = t('See section <em>Creating Your Own Instagram API Client ID</em> in <a href="/!path">jQuery Social Stream plugin documentation</a> for details.', array('!path' => $doc_file));
    }
    else {
      $form['google']['#description'] = t('See section <em>Creating Your Own Google API Key</em> in jQuery Social Stream plugin documentation for details (file <em>index.html</em> in plugin root directory).');
      $form['instagram']['#description'] = t('See section <em>Creating Your Own Instagram API Client ID</em> in jQuery Social Stream plugin documentation for details (file <em>index.html</em> in plugin root directory).');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('jquery_social_stream.settings');

    // Twitter.
    foreach (Element::children($form['twitter']) as $variable) {
      $config->set($variable, $form_state->getValue($variable));
    }

    // Google +.
    foreach (Element::children($form['google']) as $variable) {
      $config->set($variable, $form_state->getValue($variable));
    }

    // Instagram.
    foreach (Element::children($form['instagram']) as $variable) {
      $config->set($variable, $form_state->getValue($variable));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }
}
