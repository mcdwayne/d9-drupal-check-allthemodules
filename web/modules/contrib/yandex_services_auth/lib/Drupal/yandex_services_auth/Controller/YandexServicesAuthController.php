<?php

/**
 * @file
 * Contains \Drupal\action\Controller\YandexServicesAuthController
 */

namespace Drupal\yandex_services_auth\Controller;


use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Guzzle\Http\Client;
use \Guzzle\Http\Exception\BadResponseException;
use \Guzzle\Http\Exception\RequestException;

/**
 * Controller providing oauth callback for the site authorization on Yandex.
 */
class YandexServicesAuthController implements ContainerInjectionInterface {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \Guzzle\Http\Client
   */
  protected $httpClient;

  /**
   * Class constructor.
   *
   * @param \Guzzle\Http\Client
   *   The Guzzle HTTP client.
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_default_client'));
  }

  /**
   * Registers authorization result.
   */
  public function oauthCallback() {
    $request = Request::createFromGlobals();

    $code = $request->get('code', '');

    if (empty($code)) {
      watchdog('yandex_services_auth', 'The "code" parameter is empty.', array(), WATCHDOG_WARNING);
      drupal_set_message(t('An error has occurred. Please try again.'), 'error');
      return new RedirectResponse(url('admin/config/system/yandex_services_auth'));
    }

    $client_id = \Drupal::state()->get('yandex_services_auth_client_id') ?: '';
    $client_secret = \Drupal::state()->get('yandex_services_auth_client_secret') ?: '';

    $data = 'grant_type=authorization_code&client_id=' . $client_id . '&code=' . $_GET['code'];

    if (!empty($client_secret)) {
      $data .= '&client_secret=' . $client_secret;
    }

    try {
      $response = $this->httpClient->post('https://oauth.yandex.ru/token', NULL, $data)->send();
      $data = $response->getBody(TRUE);
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      watchdog('yandex_services_auth', 'Failed to retrieve token data due to "%error".', array('%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()), WATCHDOG_WARNING);
//      watchdog_exception('yandex_services_auth', $e, 'Failed to retrieve token data');
      drupal_set_message(t('An error has occurred. Please try again.'), 'error');
      return new RedirectResponse(url('admin/config/system/yandex_services_auth'));
    }
    catch (RequestException $e) {
      watchdog('yandex_services_auth', 'Failed to retrieve token data due to "%error".', array('%error' => $e->getMessage()), WATCHDOG_WARNING);
//      watchdog_exception('yandex_services_auth', $e, 'Failed to retrieve token data');
      drupal_set_message(t('An error has occurred. Please try again.'), 'error');
      return new RedirectResponse(url('admin/config/system/yandex_services_auth'));
    }
    catch (\Exception $e) {
      watchdog('yandex_services_auth', 'Failed to retrieve token data due to "%error".', array('%error' => $e->getMessage()), WATCHDOG_WARNING);
//      watchdog_exception('yandex_services_auth', $e, 'Failed to retrieve token data');
      drupal_set_message(t('An error has occurred. Please try again.'), 'error');
      return new RedirectResponse(url('admin/config/system/yandex_services_auth'));
    }

    $response = json_decode($data);
    \Drupal::state()->set('yandex_services_auth_token', $response->access_token);

    watchdog('yandex_services_auth', 'Token request is successful.');
    drupal_set_message(t('Congratulations! Your application has been authorized by Yandex.'));
    return new RedirectResponse(url('admin/config/system/yandex_services_auth'));
  }
}
