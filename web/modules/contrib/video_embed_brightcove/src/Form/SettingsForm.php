<?php

namespace Drupal\video_embed_brightcove\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Video Embed Brightcove module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new SettingsForm instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    parent::__construct($config_factory);

    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_embed_brightcove_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'video_embed_brightcove.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('video_embed_brightcove.settings');

    $url = URL::fromUri('https://studio.brightcove.com/products/videocloud/admin/oauthsettings', [
      'attributes' => [
        'target' => '_blank',
      ],
    ]);

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Brightcove API Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('The Client ID of the Brightcove API Authentication credentials, available @link. Required for thumbnail download (used for video lazy load).', [
        '@link' => Link::fromTextAndUrl($this->t('here'), $url)->toString(),
      ]),
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Brightcove API Secret Key'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('The Secret Key associated with the Client ID above. Required for thumbnail download (used for video lazy load).'),
    ];
    $form['autoplay_player'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Autoplay player name'),
      '#default_value' => $config->get('autoplay_player'),
      '#description' => $this->t('The player name to be used when autoplay is enabled (instead of player name from the input URL). Autoplay option needs to be enabled inside Brightcove UI.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $client_id = $form_state->getValue('client_id');
    $client_secret = $form_state->getValue('client_secret');

    // Check authentication if keys are provided.
    if (!empty($client_id) || !empty($client_secret)) {
      $auth_uri = 'https://oauth.brightcove.com/v4/access_token';
      $auth_string = base64_encode($client_id . ':' . $client_secret);
      $auth_options = [
        'headers' => [
          'Authorization' => 'Basic ' . $auth_string,
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'body' => 'grant_type=client_credentials',
      ];
      try {
        $this->httpClient->request('POST', $auth_uri, $auth_options);
      }
      catch (\Exception $e) {
        // Set error if authentication was not successful.
        $form_state->setErrorByName('client_id', 'Brightcove API authentication failed.');
        $form_state->setErrorByName('client_secret', 'Please check client ID and secret key.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('video_embed_brightcove.settings');
    $config->set('client_id', trim($form_state->getValue('client_id')));
    $config->set('client_secret', trim($form_state->getValue('client_secret')));
    $config->set('autoplay_player', trim($form_state->getValue('autoplay_player')));
    $config->save();
  }

}
