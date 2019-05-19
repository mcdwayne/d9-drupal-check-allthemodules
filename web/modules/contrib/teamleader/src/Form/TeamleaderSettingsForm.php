<?php

namespace Drupal\teamleader\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\teamleader\TeamleaderApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Teamleader Integration settings for this site.
 */
class TeamleaderSettingsForm extends ConfigFormBase {

  /**
   * The Teamleader API service.
   *
   * @var \Drupal\teamleader\TeamleaderApiInterface
   */
  protected $teamleaderApi;

  /**
   * TeamleaderSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\teamleader\TeamleaderApiInterface $teamleader_api
   *   The Teamleader API service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TeamleaderApiInterface $teamleader_api) {
    parent::__construct($config_factory);
    $this->teamleaderApi = $teamleader_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('teamleader_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'teamleader_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['teamleader.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('teamleader.settings');

    // Finish Teamleader Oauth2 authorization flow, if applicable.
    $this->teamleaderApi->finishAuthorization();

    $form['credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('Credentials'),
      '#open' => TRUE,
    ];

    $form['credentials']['info'] = [
      '#type' => 'details',
      '#title' => t('Teamleader Integration settings'),
      '#open' => TRUE,
      '#description' => $this->t(
        'To retrieve your client ID & secret: <ol>
          <li>Visit <a href="@url">Teamleader Marketplace</a> and create a new integration.</li>
          <li>Retrieve your client ID/Secret under the "OAuth2 credentials" header.</li>
          </ol>',
        [
          '@url' => 'https://marketplace.teamleader.eu/be/en/build',
        ]
      ),
    ];

    $form['credentials']['client_id'] = [
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#default_value' => $config->get('credentials.client_id'),
      '#required' => TRUE,
      '#size' => 100,
      '#maxlength' => 150,
      '#description' => t('The Teamleader integration client ID.'),
    ];

    $form['credentials']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Client secret'),
      '#default_value' => $config->get('credentials.client_secret'),
      '#required' => TRUE,
      '#description' => t('The Teamleader integration Client secret.'),
    ];

    $form['actions']['submit']['#value'] = $this->t('Connect to Teamleader');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('teamleader.settings')
      ->set('credentials.client_id', $form_state->getValue('client_id'))
      ->set('credentials.client_secret', $form_state->getValue('client_secret'))
      ->save();

    // Start Teamleader API Oauth2 authorization flow after saving credentials.
    $redirect_url = $this->teamleaderApi->startAuthorization();
    // Redirect to Teamleader authorization page.
    $response = new TrustedRedirectResponse($redirect_url->toString());
    $form_state->setResponse($response);
  }

}
