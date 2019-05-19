<?php

namespace Drupal\social_feed_fetcher\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthorizationCodeController extends ControllerBase {

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * AuthorizationCodeController constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(MessengerInterface $messenger, RequestStack $requestStack, ConfigFactoryInterface $configFactory) {
    $this->messenger = $messenger;
    $this->requestStack = $requestStack;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }

  /**
   * Catch response from Linkedin authentication to get an authorization code.
   *
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getResponse(Request $request) {
    $process = FALSE;
    if ($request->query->has('code')) {
      $code = $request->query->get('code');
      $process = $this->getAccessToken($code);
    }

    $url = Url::fromRoute('social_feed_fetcher.settings');
    if ($process) {
      $this->messenger->addMessage($this->t('Register success'), $this->messenger::TYPE_STATUS);
      return new RedirectResponse($url->toString());
    }
    $this->messenger->addMessage($this->t('Register non success'), $this->messenger::TYPE_ERROR);
    return new RedirectResponse($url->toString());
  }

  /**
   * Call to linkedin api to get an access token and expires_in value.
   *
   * @param $code
   *   The authorization token.
   *
   * @return bool
   *   The result of request.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getAccessToken($code) {
    $client = new Client([
      'base_uri' => 'https://www.linkedin.com',
      'allow_redirects' => TRUE,
      'timeout' => 0,
    ]);

    $redirect_url =  $this->requestStack->getCurrentRequest()->getHost();
    $config = $this->configFactory->getEditable('social_feed_fetcher.settings');

    $response = $client->request(
      'POST',
      '/oauth/v2/accessToken',
      [
        'headers' => [
          'Content-Type' => "application/x-www-form-urlencoded",
        ],
        'form_params' => [
          'client_id' => $config->get('linkedin_client_id'),
          'client_secret' => $config->get('linkedin_secret_app'),
          'code' => $code,
          'grant_type' => 'authorization_code',
          'redirect_uri' => 'https://' . $redirect_url . '/oauth/callback',
        ]
      ]
    );

    if (isset($response)) {
      $data = $response->getBody()->getContents();
      $content = Json::decode($data);
      return $this->setAccessToken($content);
    }

    return FALSE;
  }

  /**
   * Set as variable the value of access token and expires_in.
   *
   * @param $content
   *   The access token and the expires_in value.
   *
   * @return bool
   */
  protected function setAccessToken($content) {
    if (isset($content['access_token']) && isset($content['expires_in'])) {
      $this->state()->set('access_token', $content['access_token']);
      $this->state()->set('expires_in', $content['expires_in']);
      $this->state()->set('expires_in_save', time());

      return TRUE;
    }
    return FALSE;
  }

}
