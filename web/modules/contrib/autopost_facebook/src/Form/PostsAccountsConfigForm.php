<?php

namespace Drupal\autopost_facebook\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Facebook\Facebook;
use Drupal\Core\Url;

/**
 * Class AppConfigForm.
 */
class PostsAccountsConfigForm extends ConfigFormBase {

  protected $personalFacebook;

  protected $facebookPages;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['autopost_facebook.settings', 'autopost_facebook.accounts_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autopost_facebook_accounts_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autopost_facebook.settings');

    // Build accounts table.
    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Type'),
        $this->t('Name'),
        $this->t('Post on feed'),
      ],
      '#empty' => $this->t('First add App ID and secret then register a Facebook account'),
    ];

    if ($config->get('access_token')) {
      $accounts = $this->getAccountsData();
      foreach ($accounts as $account_id => $account) {
        $form['table'][$account_id] = $this->buildAccountRow($this->t('Page'),
          $account);
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $accounts_available = $this->getAccountsData();
    foreach ($form_state->getValue('table') as $account_id => $data) {
      $settings = reset($data);
      if ($settings['post'] && !array_key_exists($account_id, $accounts_available)) {
        $form_state->setErrorByName('table][' . $account_id,
          'Account not available for Facebook user.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $post_accounts = [];
    foreach ($form_state->getValue('table') as $account_id => $data) {
      $settings = reset($data);
      if ($settings['post']) {
        $post_accounts[$account_id] = $settings['access_token'];
      }
    }
    $this->config('autopost_facebook.accounts_settings')
      ->set('posts', $post_accounts)
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Gets user's accounts data.
   *
   * @return array
   *   Facebook accounts data.
   */
  protected function getAccountsData() {
    $config = $this->config('autopost_facebook.settings');
    $fb = new Facebook([
      'app_id' => $config->get('app_id'),
      'app_secret' => $config->get('app_secret'),
      'default_graph_version' => 'v2.10',
    ]);

    // Query Graph API endpoint.
    $accounts = [];
    try {
      $access_token = (string) $config->get('access_token');
      // Get personal facebook data.
      $personal_facebook = $fb
        ->get('/me', $access_token)
        ->getDecodedBody();
      $personal_facebook['access_token'] = $access_token;
      $accounts[$personal_facebook['id']] = $personal_facebook;
      // Get facebook pages data.
      $facebook_pages = $fb
        ->get('/me/accounts', $access_token)
        ->getDecodedBody();
      if (isset($facebook_pages['data'])) {
        foreach ($facebook_pages['data'] as $facebook_page) {
          $accounts[$facebook_page['id']] = $facebook_page;
        }
      }
    }
    catch (FacebookResponseException $e) {
      $message = $this->t('Graph returned an error: %message', ['%message' => $e->getMessage()]);
    }
    catch (FacebookSDKException $e) {
      $message = $this->t('Facebook SDK returned an error: %message', ['%message' => $e->getMessage()]);
    }
    catch (\Exception $e) {
      $message = $this->t('Posting to Facebook failed: %message', ['%message' => $e->getMessage()]);
    }

    if (isset($message)) {
      drupal_set_message($message, 'error');
    }

    return $accounts;
  }

  /**
   * Gets data of facebook pages associated to the user's personal facebook.
   *
   * @return array
   *   Facebook pages data.
   */
  protected function getFacebookPagesData() {
    $config = $this->config('autopost_facebook.settings');
    $fb = new Facebook([
      'app_id' => $config->get('app_id'),
      'app_secret' => $config->get('app_secret'),
      'default_graph_version' => 'v2.10',
    ]);

    // Query Graph API endpoint.
    $results = [];
    try {
      $results = $fb->get('/me/accounts', (string) $config->get('access_token'))
        ->getDecodedBody();
    }
    catch (FacebookResponseException $e) {
      $message = $this->t('Graph returned an error: %message', ['%message' => $e->getMessage()]);
    }
    catch (FacebookSDKException $e) {
      $message = $this->t('Facebook SDK returned an error: %message', ['%message' => $e->getMessage()]);
    }
    catch (\Exception $e) {
      $message = $this->t('Posting to Facebook failed: %message', ['%message' => $e->getMessage()]);
    }
    if (isset($message)) {
      drupal_set_message($message, 'error');
    }

    return isset($results['data']) ? $results['data'] : [];
  }

  /**
   * Builds the render array for each row in accounts table.
   *
   * @param string $type
   *   Text to go in the "Type" column.
   * @param array $account_data
   *   Facebook account data.
   *
   * @return array
   *   Render array for the row in accounts table.
   */
  protected function buildAccountRow($type, array $account_data) {
    $post_accounts = $this->config('autopost_facebook.accounts_settings')
      ->get('posts');
    $row = [
      'type' => [
        '#markup' => $type,
      ],
      'account' => [
        '#type' => 'link',
        '#title' => isset($account_data['name']) ? $account_data['name'] : '',
        '#url' => isset($account_data['id']) ?
        Url::fromUri("https://facebook.com/{$account_data['id']}") : '',
      ],
      [
        'access_token' => [
          '#type' => 'hidden',
          '#value' => $account_data['access_token'],
        ],
        'post' => [
          '#type' => 'checkbox',
          '#default_value' => array_key_exists($account_data['id'], $post_accounts),
        ],
      ],
    ];
    return $row;
  }

}
