<?php

namespace Drupal\okta_saml_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SigninController.
 *
 * @package Drupal\okta_saml_login\Controller
 */
class SigninController extends ControllerBase {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public $requestStack;

  /**
   * SigninController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   An instance of ConfigFactory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ConfigFactory $config,
                              RequestStack $request_stack) {
    $this->config = $config->get('okta_api.settings');
    $this->widgetConfig = $config->get('okta_saml_login.widget.config');
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function signin() {
    $widgetConfigTemp = $this->widgetConfig->get();

    // TODO Find a nicer way to str_replace all keys containing
    // _@_ to . example primaryauth_@_title to primaryauth.title
    // See https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Config%21ConfigBase.php/function/ConfigBase%3A%3AvalidateKeys/8.2.x
    $widgetConfigReplaced = str_replace('_@_', '.', Yaml::dump($widgetConfigTemp));
    $widgetConfig = Yaml::parse($widgetConfigReplaced);

    // Add the Okta domian dynamically.
    $widgetConfig['baseUrl'] = $this->getOktaDomain();

    return [
      '#theme' => 'okta_saml_login_signin_widget',
      '#title' => 'Sign in',
      '#vars' => [],
      '#attached' => [
        'library' => [
          'okta_saml_login/okta_saml_login.okta',
        ],
        'drupalSettings' => [
          'okta_saml_login' => [
            'redirect_url' => $this->getRedirectUrl(),
          ],
          'okta_saml_config' => $widgetConfig,
        ],
      ],
    ];

  }

  /**
   * Get Okta Domain.
   *
   * @return string
   *   Url.
   */
  private function getOktaDomain() {
    $oktaApiDomain = $this->config->get('okta_domain');
    $oktaPreviewDomain = $this->config->get('preview_domain');

    if ($oktaPreviewDomain == TRUE) {
      $oktaApiDomain = 'oktapreview.com';
    }

    return 'https://' . $this->config->get('organisation_url') . '.' . $oktaApiDomain;
  }

  /**
   * Get Redirect Url.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Url.
   */
  private function getRedirectUrl() {
    $request = $this->requestStack->getCurrentRequest();

    $returnToUrl = '/';

    // See if a URL has been explicitly provided in ReturnTo.
    if ($request->query->get('ReturnTo')) {
      $returnToUrl = $request->query->get('ReturnTo');
    }

    $returnTo = Url::fromUserInput($returnToUrl, ['absolute' => TRUE])->toString();

    $redirectUrl = Url::fromRoute(
      'simplesamlphp_auth.saml_login',
      [],
      [
        'absolute' => TRUE,
        'query' => ['ReturnTo' => $returnTo],
      ]
    )->toString();

    return $redirectUrl;
  }

}
