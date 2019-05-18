<?php

namespace Drupal\itsyouonline\Controller;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\itsyouonline\ItsyouonlineUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * MailChimp Webhook controller.
 */
class ItsyouonlineAuthorizeController extends ControllerBase {
  const AUTHORIZE_URL = 'https://itsyou.online/v1/oauth/authorize?';
  const ACCESS_TOKEN_URL = 'https://itsyou.online/v1/oauth/access_token?';

  /**
   * {@inheritdoc}
   */
  public function process($processType) {
    $tempstore = ItsyouonlineUtils::session();
    $tempstore->set('itsyouonline_token', user_password(32));
    $tempstore->set('itsyouonline_action', $processType);

    $this->authorize();
  }

  public function authorize() {
    $tempstore = ItsyouonlineUtils::session();

    if (empty($tempstore->get('itsyouonline_token')) ||
      empty($tempstore->get('itsyouonline_action'))) {
      throw new ServiceUnavailableHttpException();
    }

    $config = \Drupal::config('itsyouonline.account');
    $clientId =  $config->get('client_id');
    $clientSecret = $config->get('client_secret');
    $redirectUrl = Url::fromRoute('itsyouonline.authorize', array(), array('absolute' => TRUE))->toString();

    $state_array = array(
      'token' => $tempstore->get('itsyouonline_token'),
      'action' => $tempstore->get('itsyouonline_action')
    );

    $hash = self::encodeState($state_array);
    $code = \Drupal::request()->query->get('code');

    if (!$code) {
      $params = array(
        'client_id' => $clientId,
        'response_type' => 'code',
        'scope' => _itsyouonline_scope_params(true),
        'redirect_uri' => $redirectUrl,
        'state' => $hash
      );

      if ($tempstore->get('itsyouonline_action') === 'register') {
        $params['prefer'] = 'register';
      }

      $paramQuery = http_build_query($params);

      $redirect = new RedirectResponse(self::AUTHORIZE_URL . $paramQuery);
      return $redirect->send();
    } else {
      if (\Drupal::request()->query->get('state') !== $hash) {
        ItsyouonlineUtils::logger()->error(t('Authorize callback error, oauth - state does not match'));
        throw new ServiceUnavailableHttpException();
      }

      $params = array(
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'code' => $code,
        'redirect_uri' => $redirectUrl,
        'state' => $hash
      );

      $paramQuery = http_build_query($params);

      $httpClient = \Drupal::httpClient();
      try {
        $response = $httpClient->request('POST', self::ACCESS_TOKEN_URL . $paramQuery, array(
          'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
        ));

        $result = $response->getBody()->getContents();
        $statusCode = $response->getStatusCode();
      } catch (\Exception $e) {
        watchdog_exception('itsyouonline', $e->getMessage());

        return t('Error while authenticating user with itsyou.');
      }

      switch ($statusCode) {
        case 200:
        case 301:
        case 302:
          $resp = json_decode($result);

          if (json_last_error()) {
            ItsyouonlineUtils::logger()->error('Authorize callback error, oauth - error while loading getting access key');
            return t('Error while authenticating user with itsyou.');
          }
          
          self::postAuthorize($resp, $state_array['action']);

        break;

        default:
          ItsyouonlineUtils::logger()->error('Authorize callback error, oauth - error while loading getting access key');
          return t('Error while authenticating user with itsyou.');
      }
    }
  }


  /**
   * Encodes an array into a Base64 string.
   *
   * This function encodes an array into a Base64 string so that it can be used
   * as a value to the state parameter
   *
   * @param array $state_array
   *   The array containing the values which have to be encoded.
   *
   * @return string
   *   The Base64 representation of the array.
   */
  private static function encodeState(array $stateArray) {
    return base64_encode(json_encode($stateArray));
  }

  /**
   * Decodes a Base64 string into an array.
   *
   * This function decodes a Base64 string into an array. 
   *
   * @param string $state_string
   *   The Base64 string received by the callback function.
   *
   * @return array
   *   The decoded Base64 string.
   */
  private static function decodeState($stateString) {
    return json_decode(base64_decode($stateString, TRUE), TRUE);
  }

  private function postAuthorize($resp, $processType) {
    $userInfo = ItsyouonlineUtils::getItsyouUserInfo($resp->info->username, $resp);

    if (!$userInfo) {
      return t('Error while loading itsyou user details');
    }

    $userInfo['authdata'] = $resp;

    $itsyouonline = array();

    foreach (_itsyouonline_scope_params_attributes() as $param) {
      switch ($param) {
        case 'username':
        case 'firstname':
        case 'lastname':
          if (isset($userInfo[$param])) {
            $itsyouonline[$param] = $userInfo[$param];
          }
        break;

        case 'email':
          if (!empty($userInfo['emailaddresses']) && is_array($userInfo['emailaddresses'])) {
            $itsyouonline[$param] = $userInfo['emailaddresses'][0]['emailaddress'];
          }
        break;

      }
    }

    $tempstore = ItsyouonlineUtils::session();
    $tempstore->set('itsyouonline_uid', $itsyouonline['username']);
    $tempstore->set('itsyouonline_auth', $resp);

    return ItsyouonlineUtils::processIntegration($processType, $itsyouonline, $userInfo['authdata']);
  }
}
