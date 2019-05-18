<?php

namespace Drupal\janrain_connect_social\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\URl;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\janrain_connect\Service\JanrainConnectConnector;

/**
 * Form that handles merge social accounts.
 */
class SocialMergeForm extends FormBase {

  /**
   * Symfony session handler.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  /**
   * Janrain Connect Route Match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * JanrainConnectConnector.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectConnector
   */
  private $janrainConnector;

  /**
   * {@inheritdoc}
   */
  public function __construct(Session $session, CurrentRouteMatch $route_match, LanguageManagerInterface $language_manager, JanrainConnectConnector $janrain_connector) {
    $this->session = $session;
    $this->routeMatch = $route_match;
    $this->languageManager = $language_manager;
    $this->janrainConnector = $janrain_connector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session'),
      $container->get('current_route_match'),
      $container->get('language_manager'),
      $container->get('janrain_connect.connector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_social_merge';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $provider = NULL) {
    $message = $this->t('You already registered using your <strong>@provider</strong> account. We can merge your accounts, so you are able to login using both your accounts.', [
      '@provider' => Unicode::ucfirst($provider),
    ]);

    if ($provider) {
      $form['message'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $message,
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If users clicks on 'Cancel', redirect them.
    if ('edit-cancel' == $form_state->getTriggeringElement()['#id']) {
      $form_state->setRedirect('<front>');
      return;
    }

    $config = $this->config('janrain_connect.settings');
    // Figure out which language to use.
    // Default is en-US.
    $locale = $config->get('default_language');
    $lid = $this->languageManager->getCurrentLanguage()->getId();
    $language = $config->get('flow_language_mapping_' . $lid);
    if ($language) {
      $locale = $language;
    }
    $provider = $this->routeMatch->getParameter('provider');

    // This code will merge social account with another social account.
    // Redirect users to the social media page, so they can log in
    // to proof they own both accounts.
    $token_url = Url::fromRoute('janrain_connect_social.merge_handler', [], ['absolute' => TRUE])->toString();

    $redirect = new TrustedRedirectResponse($this->janrainConnector->getSocialProviders($provider, $token_url, $locale));

    $form_state->setResponse($redirect);
  }

}
