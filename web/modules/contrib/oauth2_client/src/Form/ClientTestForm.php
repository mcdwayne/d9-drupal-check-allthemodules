<?php

namespace Drupal\oauth2_client\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface;
use Drupal\oauth2_client\Service\Oauth2ClientServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the form for testing OAuth2 Client integrations.
 */
class ClientTestForm extends FormBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The Drupal tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempstore;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The OAuth2 Client plugin manager.
   *
   * @var \Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface
   */
  protected $oauth2ClientPluginManager;

  /**
   * The OAuth2 Client service.
   *
   * @var \Drupal\oauth2_client\Service\Oauth2ClientServiceInterface
   */
  protected $oauth2ClientService;

  /**
   * Constructs a ClientTestForm object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstoreFactory
   *   The Drupal private tempstore factory.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The URL generator service.
   * @param \Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface $oauth2ClientPluginManager
   *   The OAuth2 Client plugin manager.
   * @param \Drupal\oauth2_client\Service\Oauth2ClientServiceInterface $oauth2ClientService
   *   The OAuth2 client service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   Current route match.
   */
  public function __construct(
    RequestStack $requestStack,
    PrivateTempStoreFactory $tempstoreFactory,
    UrlGeneratorInterface $urlGenerator,
    Oauth2ClientPluginManagerInterface $oauth2ClientPluginManager,
    Oauth2ClientServiceInterface $oauth2ClientService,
    RouteMatchInterface $currentRouteMatch
  ) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->tempstore = $tempstoreFactory->get('oauth2_client');
    $this->urlGenerator = $urlGenerator;
    $this->oauth2ClientPluginManager = $oauth2ClientPluginManager;
    $this->oauth2ClientService = $oauth2ClientService;
    $this->routeMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('tempstore.private'),
      $container->get('url_generator'),
      $container->get('oauth2_client.plugin_manager'),
      $container->get('oauth2_client.service'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oauth2_client_client_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#prefix'] = '<div id="oauth2_client_client_test_form_wrapper">';
    $form['#suffix'] = '</div>';

    // Get a list of the OAuth2 Client plugin definitions.
    $definitions = $this->oauth2ClientPluginManager->getDefinitions();

    if (!empty($definitions)) {

      /*
       * This form tests the OAuth2 Clients by redirecting to the OAuth2 server,
       * which after authentication (on that server) redirects the user back to
       * a URL on this server. For this testing form, the redirect URL is given
       * as the URL of the page on which the form exists (using the special
       * <current> route). The problem is that when the user is redirected back,
       * we need to re-request the access token, using the state and code values
       * from the URL. This all works fine, however leaving the 'code' and
       * 'state' variables in the URL causes issues on further form submissions,
       * so to get around it, the user is redirected back to this same form
       * without the code and state variables in it.
       */
      // Only do something if the state parameter exists in the URL.
      if ($state = $this->currentRequest->query->get('state')) {
        // Loop through the definitions.
        foreach ($definitions as $definition) {
          // Check if there is a value in the tempstore (the Drupal SESSION) for
          // 'state' for the current OAuth2 Client.
          if ($stored_state = $this->tempstore->get('oauth2_client_state-' . $definition['id'])) {
            // Re-request the access token. That script will complete the
            // retrieval of the authorization token from the OAuth2 server.
            $this->oauth2ClientService->getAccessToken($definition['id']);

            // Redirect back to this page, with the 'code' and 'state' variables
            // removed from the URL.
            $url = $this->urlGenerator->generateFromRoute('<current>', ['plugin' => $definition['id']]);
            $redirect = new RedirectResponse($url);
            $redirect->send();

            exit();
          }
        }
      }

      // Create an array of key => name value pairs of the OAuth2 Client plugins
      // to be used as the #options in the form, allowing users to select the
      // plugin on the system to test.
      $options = array_map(function ($definition) {
        return $definition['name'];
      }, $definitions);

      $form['plugin'] = [
        '#type' => 'container',
      ];

      // Determine the definition plugin that should be displayed (if any).
      $definition_key = $form_state->getValue('oauth2_client_plugin');
      if (!$definition_key) {
        $definition_key = $this->routeMatch->getParameter('plugin');
      }

      // Create a select list of plugins, so users can choose a plugin to test.
      $form['plugin']['oauth2_client_plugin'] = [
        '#type' => 'select',
        '#title' => t('Plugin to test'),
        '#options' => ['' => $this->t('- SELECT PLUGIN -')] + $options,
        '#default_value' => $definition_key,
      ];

      // A non-AJAX submit button is used, as the redirects with OAuth2 don't
      // work well with AJAX.
      $form['plugin']['set_plugin'] = [
        '#type' => 'submit',
        '#value' => $this->t('Apply'),
        '#submit' => ['::setPlugin'],
      ];

      // If a plugin has been selected, show more info.
      if ($definition_key) {
        // Get the current definition.
        $definition = $definitions[$definition_key];
        // Walk through the array to make each key a human readable value.
        array_walk($definition, function (&$value, $key) {
          $value = $key . ': ' . $value;
        });

        // Display the plugin info to the user.
        $form['oauth2_client_plugin_info'] = [
          '#prefix' => '<h2>' . $this->t('Plugin Info') . '</h2><pre>',
          '#suffix' => '</pre>',
          '#markup' => implode('<br />', $definition),
        ];

        $form['actions'] = [
          '#type' => 'actions',
        ];

        // Create the button that will test the authentication on the remote
        // server.
        $form['actions']['test_plugin'] = [
          '#type' => 'submit',
          '#value' => $this->t('Test Plugin'),
          '#submit' => [
            '::testPlugin',
          ],
        ];

        // Create a button to clear the access token altogether.
        $form['actions']['clear_access_token'] = [
          '#type' => 'submit',
          '#value' => $this->t('Clear Access Token'),
          '#submit' => [
            '::clearAccessToken',
          ],
        ];

        // Retrieve a stored access token if one exists.
        $access_token = $this->oauth2ClientService->retrieveAccessToken($definition_key);
        // Check if a token was retrieved.
        if ($access_token) {
          $values = [
            $this->t('Access Token: @token', ['@token' => $access_token->getToken()]),
            $this->t('Refresh Token: @token', ['@token' => $access_token->getRefreshToken()]),
            $this->t('Expires: @expires', ['@expires' => $access_token->getExpires()]),
            $this->t('Has Expired: @expired', ['@expired' => ($access_token->getExpires() && $access_token->hasExpired() ? t('Yes') : t('No'))]),
          ];

          // Display the token details to the user.
          $form['access_token'] = [
            '#prefix' => '<h2>' . $this->t('Current Access Token') . '</h2><pre>',
            '#suffix' => '</pre>',
            '#markup' => implode('<br/>', $values),
          ];
        }
        // No access token was found.
        else {
          $form['no_access_token'] = [
            '#prefix' => '<h2>' . $this->t('Current Access Token') . '</h2><pre>',
            '#suffix' => '</pre>',
            '#markup' => $this->t('No access token has been stored'),
          ];
        }
      }
    }
    else {
      $form['no_plugins'] = [
        '#prefix' => '<p><em>',
        '#suffix' => '</em></p>',
        '#markup' => $this->t('No OAuth2 Client plugins found'),
      ];
    }

    return $form;
  }

  /**
   * Ajax callback form the Oauth2 Client Tester form.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Set the plugin to test.
   */
  public function setPlugin(array &$form, FormStateInterface $form_state) {
    // The current plugin is set through the URL (so as to allow redirects from
    // OAuth2 servers back to this page). As such, when a plugin is selected,
    // a redirect needs to happen.
    if ($plugin = $form_state->getValue('oauth2_client_plugin')) {
      $form_state->setRedirect('oauth2_client.reports.tester.plugin', ['plugin' => $plugin]);
    }
    else {
      $form_state->setRedirect('oauth2_client.reports.tester');
    }
  }

  /**
   * Test the seledted plugin by authenticating to the OAuth2 Server.
   */
  public function testPlugin(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    // Attempt to retrieve an access token.
    $this->oauth2ClientService->getAccessToken($form_state->getValue('oauth2_client_plugin'));
  }

  /**
   * Clear the authorization token for the selected OAuth2 Client.
   */
  public function clearAccessToken(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $this->oauth2ClientService->clearAccessToken($form_state->getValue('oauth2_client_plugin'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
