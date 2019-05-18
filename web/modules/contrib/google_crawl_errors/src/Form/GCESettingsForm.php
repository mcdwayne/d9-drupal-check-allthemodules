<?php

namespace Drupal\google_crawl_errors\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure Google Crawl Errors settings.
 */
class GCESettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_crawl_errors_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_crawl_errors.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_crawl_errors.settings');

    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#default_value' => $config->get('site_id'),
    ];

    $form['site_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Url'),
      '#default_value' => $config->get('site_url'),
      '#description' => t("The site's URL, including protocol. Example: https://@domain/", ['@domain' => $_SERVER['HTTP_HOST']]),
    ];

    $form['oauth_secret_json'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Google OAuth Secret JSON'),
      '#default_value' => $config->get('oauth_secret_json'),
    ];

    $oauth_url = Url::fromRoute('google_crawl_errors.oauth');
    $oauth_url_html_link = Link::fromTextAndUrl('OAuth redirect URL', $oauth_url)->toString();
    $form['oauth_token_json'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Google OAuth Token JSON'),
      '#default_value' => $config->get('oauth_token_json'),
      '#description' => $this->t('Get the token by going to the @url then copy and paste the result JSON here.',
        ['@url' => $oauth_url_html_link]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('google_crawl_errors.settings')
      ->set('site_id', $form_state->getValue('site_id'))
      ->set('site_url', $form_state->getValue('site_url'))
      ->set('oauth_secret_json', $form_state->getValue('oauth_secret_json'))
      ->set('oauth_token_json', $form_state->getValue('oauth_token_json'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
