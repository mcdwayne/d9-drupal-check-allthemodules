<?php

namespace Drupal\twitter_entity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class TwitterSettingsForm.
 *
 * @package Drupal\twitter_entity\Form
 */
class TwitterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'twitter_entity.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twitter_entity.settings');

    $twitterAppUrl = Url::fromUri('https://dev.twitter.com/apps', [
      'attributes' => [
        'target' => '_blank',
      ],
    ]);
    $twitterAppUrl = Link::fromTextAndUrl('https://dev.twitter.com/apps', $twitterAppUrl);

    $markupText = $this->t('In order to finish module setup create twitter app @twitter_app_url',
      ['@twitter_app_url' => $twitterAppUrl->toString()]
    );

    $form['twitter_app'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $markupText,
    ];

    $form['twitter_user_names'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Twitter user name/names'),
      '#description' => $this->t('A list of Twitter accounts. Enter one or more user name on each line.'),
      '#required' => TRUE,
      '#default_value' => $config->get('twitter_user_names'),
    ];

    $form['tweets_number_per_request'] = [
      '#type' => 'number',
      '#title' => $this->t('Tweets number to pull'),
      '#description' => $this->t('Tweets number to pull per request for each user defined above.'),
      '#required' => TRUE,
      '#default_value' => $config->get('tweets_number_per_request'),
      '#attributes' => [
        'min' => 1,
        'max' => 300,
      ],
    ];

    $form['fetch_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Fetch interval'),
      '#description' => $this->t('How often check if there are some new tweets.'),
      '#default_value' => $config->get('fetch_interval'),
      '#options' => [
        60 => $this->t('1 minute'),
        300 => $this->t('5 minutes'),
        3600 => $this->t('1 hour'),
        86400 => $this->t('1 day'),
      ],
    ];

    $nextExecution = $config->get('next_execution');
    if (!empty($nextExecution)) {
      $fetchDate = date('d-m-Y G:i', $nextExecution);
      $form['interval_text'] = [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t('Next fetch on @fetchDate',
          ['@fetchDate' => $fetchDate]
        ),
      ];
    }

    // Manual pull link.
    $manualPullLink = Link::createFromRoute($this->t('Manual pull'), 'twitter_entity.manual_pull');

    $form['manual_pull'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $manualPullLink->toString(),
    ];

    $form['keys_tokens'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter Keys and Access Tokens'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['keys_tokens']['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer Key (API Key)'),
      '#required' => TRUE,
      '#default_value' => $config->get('consumer_key'),

    ];

    $form['keys_tokens']['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer Secret (API Secret)'),
      '#required' => TRUE,
      '#default_value' => $config->get('consumer_secret'),
    ];

    $form['keys_tokens']['oauth_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#required' => TRUE,
      '#default_value' => $config->get('oauth_access_token'),
    ];

    $form['keys_tokens']['oauth_access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token Secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('oauth_access_token_secret'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    $keysTokens = $values['keys_tokens'];

    if (!is_numeric($values['tweets_number_per_request']) || $values['tweets_number_per_request'] <= 0) {
      $form_state->setErrorByName('tweets_number_per_request', $this->t('Must be a number greater then 0.'));
    }

    // Init connection with twitter API.
    $connection = new TwitterOAuth(
      $keysTokens['consumer_key'],
      $keysTokens['consumer_secret'],
      $keysTokens['oauth_access_token'],
      $keysTokens['oauth_access_token_secret']
    );

    // Check if provided API information are correct.
    $accountVerify = $connection->get("account/verify_credentials");
    if (isset($accountVerify->errors)) {
      $form_state->setErrorByName('keys_tokens', $this->t('Incorrect API keys information.'));
    }
    else {
      // Validate user names only if API keys are valid.
      // Check if all provided twitter user names exist on twitter.
      $twitterUserNames = explode(PHP_EOL, $form_state->getValue('twitter_user_names'));
      $incorrectTwitterUserNames = [];

      foreach ($twitterUserNames as $userName) {
        $twitterUser = $connection->get("users/lookup", ["screen_name" => trim($userName)]);
        if (isset($twitterUser->errors)) {
          array_push($incorrectTwitterUserNames, trim($userName));
        }
      }

      // If there incorrect user names.
      if (!empty($incorrectTwitterUserNames)) {
        $form_state->setErrorByName('twitter_user_names',
          $this->t('Following twitter user names are incorrect: @users',
            ['@users' => implode(', ', $incorrectTwitterUserNames)]
          ));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    $config = $this->config('twitter_entity.settings');
    $config->set('twitter_user_names', $values['twitter_user_names']);
    $config->set('tweets_number_per_request', $values['tweets_number_per_request']);
    $config->set('fetch_interval', $values['fetch_interval']);

    $config->set('consumer_key', $values['keys_tokens']['consumer_key']);
    $config->set('consumer_secret', $values['keys_tokens']['consumer_secret']);
    $config->set('oauth_access_token', $values['keys_tokens']['oauth_access_token']);
    $config->set('oauth_access_token_secret', $values['keys_tokens']['oauth_access_token_secret']);
    $config->save();
  }

}
