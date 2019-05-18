<?php

namespace Drupal\get_linkedin_posts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use League\OAuth2\Client\Provider\LinkedIn;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Build Get Linkedin Posts settings form.
 */
class GetLinkedinPostsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'get_linkedin_posts_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['get_linkedin_posts.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('get_linkedin_posts.settings');

    $form['linkedin_pull'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Posts pull Settings'),
    ];

    $form['linkedin_pull']['linkedin_import'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable importing Linkedin Posts'),
      '#default_value' => $config->get('linkedin_import'),
    ];

    $form['linkedin_pull']['linkedin_companies'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Company ID's for import posts"),
      '#default_value' => $config->get('linkedin_companies'),
      '#description' => $this->t("Enter space-separated Company ID's."),
      '#required' => TRUE,
    ];

    $form['linkedin_pull']['linkedin_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Posts count'),
      '#default_value' => $config->get('linkedin_count'),
      '#description' => $this->t("Maximum 50 posts."),
      '#min' => 1,
      '#max' => 50,
    ];

    $intervals = [604800, 2592000, 7776000, 31536000];
    $form['linkedin_pull']['linkedin_expire'] = [
      '#type' => 'select',
      '#title' => $this->t('Delete old posts'),
      '#default_value' => $config->get('linkedin_expire'),
      '#options' => [0 => $this->t('Never')] + array_map([
        \Drupal::service('date.formatter'),
        'formatInterval',
      ],
        array_combine($intervals, $intervals)),
    ];

    $form['linkedin_oauth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Application Settings'),
      '#description' => $this->t('To enable OAuth2 based access for Linkedin, you must <a href="@url" target="_blank">register your application</a> with Linkedin, add the provided keys here and set administrator for you Linkedin application.', [
        '@url' => 'https://developer.linkedin.com/docs',
      ]),
    ];

    $form['linkedin_oauth']['redirect_uri'] = [
      '#type' => 'item',
      '#title' => $this->t('Callback URL'),
      '#markup' => Url::fromUri('base:admin/config/content/get_linkedin_posts/token', ['absolute' => TRUE])
        ->toString(),
    ];

    $form['linkedin_oauth']['linkedin_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Id'),
      '#default_value' => $config->get('linkedin_client_id'),
      '#required' => TRUE,
    ];

    $form['linkedin_oauth']['linkedin_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $config->get('linkedin_client_secret'),
      '#required' => TRUE,
    ];

    $form['linkedin_oauth']['linkedin_access_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Token'),
      '#default_value' => $config->get('linkedin_access_token'),
      '#access' => FALSE,
    ];

    $form['linkedin_oauth']['linkedin_access_token_expires'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expiry'),
      '#default_value' => $config->get('linkedin_access_token_expires'),
      '#access' => FALSE,
    ];

    $form['linkedin_oauth']['linkedin_token_message'] = [
      '#type' => 'item',
      '#title' => $this->t('Current Linkedin Token'),
      '#markup' => $this->isExpiredLinkedinToken(),
    ];

    $form['linkedin_oauth']['linkedin_admin_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Stay informed about token expire'),
      '#default_value' => $config->get('linkedin_admin_email'),
      '#placeholder' => $this->t('Enter you email'),
      '#description' => $this->t('Notification will be send in a week.'),
      '#required' => FALSE,
    ];

    $form['linkedin_oauth']['actions']['get_new_linkedin_access_token'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get new Linkedin Access Token'),
      '#submit' => ['::getNewLinkedinToken'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $this->config('get_linkedin_posts.settings')
      ->setData($values)
      ->save();

    drupal_set_message($this->t('Changes saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function getNewLinkedinToken(array &$form, FormStateInterface $form_state) {

    $this->submitForm($form, $form_state);

    $config = $this->config('get_linkedin_posts.settings');

    if ($config->get('linkedin_client_id') && $config->get('linkedin_client_secret')) {

      $provider = new LinkedIn([
        'clientId' => $config->get('linkedin_client_id'),
        'clientSecret' => $config->get('linkedin_client_secret'),
        'redirectUri' => Url::fromUri('base:admin/config/content/get_linkedin_posts/token', ['absolute' => TRUE])
          ->toString(),
      ]);

      $authUrl = $provider->getAuthorizationUrl();
      $_SESSION['oauth2state'] = $provider->getState();
      $form_state->setResponse(new TrustedRedirectResponse($authUrl));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isExpiredLinkedinToken() {
    $config = $this->config('get_linkedin_posts.settings');
    if ($config->get('linkedin_access_token') && $config->get('linkedin_access_token_expires')) {
      if ($config->get('linkedin_access_token_expires') > time()) {

        /** @var \Drupal\Core\Datetime\DateFormatterInterface $formatter */
        $date_formatter = \Drupal::service('date.formatter');
        $days = $date_formatter->formatDiff(\Drupal::time()->getRequestTime(), $config->get('linkedin_access_token_expires'), [
          'granularity' => 3,
          'return_as_object' => FALSE,
        ]);

        return $this->t(
          "Linkedin access token will be expired in a <strong>@days</strong>.
          You can generate new Linkedin access token any time you need.
          Also site administrator will be informed on the site's email before the access token will be expire (in a week).
          You can leave your email in the form below to be informed about token expire.", [
            '@days' => $days,
          ]);
      }
      else {
        return $this->t("Token expired. You need to generate new Linkedin access token");
      }
    }
    else {
      return $this->t("You need to generate new Linkedin access token");
    }
  }

}
