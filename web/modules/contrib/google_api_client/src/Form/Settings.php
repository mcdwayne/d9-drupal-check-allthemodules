<?php

namespace Drupal\google_api_client\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\google_api_client\Service\GoogleApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Google API Settings.
 */
class Settings extends ConfigFormBase {

  /**
   * Google API Client.
   *
   * @var \Drupal\google_api_client\Service\GoogleApiClient
   */
  private $googleApiClient;

  /**
   * Settings constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\google_api_client\Service\GoogleApiClient $googleApiClient
   *   Google Api Client.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              GoogleApiClient $googleApiClient) {
    parent::__construct($config_factory);
    $this->googleApiClient = $googleApiClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('google_api_client.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_api_client_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_api_client.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_api_client.settings');
    $tokenConf = $this->config('google_api_client.tokens');
    $options = ['attributes' => ['target' => '_blank']];

    $form['client'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Client Settings'),
    ];

    $form['client']['help'] = [
      '#type' => '#markup',
      '#markup' => $this->t('To get your Client Credentials, you need to register your application. See details on @link.',
        [
          '@link' => Link::fromTextAndUrl('https://developers.google.com/api-client-library/php/auth/web-app',
            Url::fromUri('https://developers.google.com/api-client-library/php/auth/web-app'))->toString(),
        ]),
    ];

    $form['client']['credentials'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Client Credentials'),
      '#default_value' => $config->get('credentials'),
      '#required' => TRUE,
    ];

    $linkScopes = Link::fromTextAndUrl('click here to learn more', Url::fromUri('https://developers.google.com/identity/protocols/googlescopes', $options))->toString();
    $form['client']['scopes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Client Scopes'),
      '#default_value' => $config->get('scopes'),
      '#required' => TRUE,
      '#description' => $this->t('Add one scope per line. OAuth 2.0 Scopes for Google APIs, @link. After changing scopes, you have to request new tokens.',
        ['@link' => $linkScopes]
      ),
    ];

    if ($config->get('credentials') != '') {
      $link = Link::fromTextAndUrl('click here', Url::fromUri($this->accessUrl(), $options))->toString();
      // Just check if any of the tokens are set, if not set a message.
      if ($tokenConf->get('google_access_token') == NULL && $tokenConf->get('google_refresh_token') == NULL) {
        $msg = $this->t('Access and Refresh Tokens are not set, to get your Tokens, @link.',
          ['@link' => $link]
        );
        // TODO fix the deprecated drupal_set_message.
        drupal_set_message($msg, 'error');
      }

      // TODO Figure out a nicer way to display the link. Maybe a button?
      $form['client']['tokens'] = [
        '#type' => 'details',
        '#title' => $this->t('Access and Refresh Tokens'),
        '#description' => $this->t('To get your Tokens, @link.',
          ['@link' => $link]
        ),
        '#open' => TRUE,
        '#access' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('google_api_client.settings')
      ->set('credentials', $form_state->getValue('credentials'))
      ->set('scopes', $form_state->getValue('scopes'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Generate the Access Url.
   *
   * See details at
   * https://developers.google.com/identity/protocols/OAuth2WebServer?csw=1#formingtheurl.
   *
   * @return string
   *   URL.
   */
  private function accessUrl() {
    // This is required when developing and in need of refresh tokens.
    // Refresh Tokens are only sent if this is set to force.
    // Since we are explicitly asking the user to refresh tokens,
    // its best to force this.
    $this->googleApiClient->googleClient->setApprovalPrompt("force");

    // Generate a URL to request access from Google's OAuth 2.0 server.
    return $this->googleApiClient->googleClient->createAuthUrl();
  }

}
