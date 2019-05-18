<?php

namespace Drupal\sms_mailup\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms_mailup\MailupAuthenticationInterface;

/**
 * Generic route controller for mailup.
 */
class MailupController extends ControllerBase {

  /**
   * The MailUp authentication service.
   *
   * @var \Drupal\sms_mailup\MailupAuthenticationInterface
   */
  protected $mailUpAuthentication;

  /**
   * Constructs a new MailupNewTokenConfirmForm object.
   *
   * @param \Drupal\sms_mailup\MailupAuthenticationInterface $mailUpAuthentication
   *   The MailUp service.
   */
  public function __construct(MailupAuthenticationInterface $mailUpAuthentication) {
    $this->mailUpAuthentication = $mailUpAuthentication;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sms_mailup.authentication')
    );
  }

  /**
   * Display information about OAuth tokens.
   */
  public function OAuthInformation(SmsGatewayInterface $sms_gateway) {
    $gateway_id = $sms_gateway->id();

    $render = [];

    $render['table'] = [
      '#type' => 'table',
    ];

    $token = $this->mailUpAuthentication->getToken($gateway_id, FALSE);

    // Initialised?
    $initialised = $token !== FALSE;
    $render['table']['initialised'][0]['#wrapper_attributes'] = ['header' => TRUE];
    $render['table']['initialised'][0]['#plain_text'] = $this->t('Initialized');
    $render['table']['initialised'][1]['#plain_text'] = $initialised ? $this->t('Yes') : $this->t('No');

    // Expired.
    $expired = $token === FALSE || (isset($token) && $token->hasExpired());
    $render['table']['expired'][0]['#wrapper_attributes'] = ['header' => TRUE];
    $render['table']['expired'][0]['#plain_text'] = $this->t('Needs refresh');
    $render['table']['expired'][1]['#plain_text'] = $expired ? $this->t('Yes') : $this->t('No');

    // Refresh time.
    $seconds = $token !== FALSE ? $token->getExpires() - REQUEST_TIME : FALSE;
    $render['table']['time_remaining'][0]['#wrapper_attributes'] = ['header' => TRUE];
    $render['table']['time_remaining'][0]['#plain_text'] = $this->t('Refresh in');
    $render['table']['time_remaining'][1]['#plain_text'] = $seconds !== FALSE ? $this->t('@seconds seconds', ['@seconds' => $seconds]) : '-';

    // Details.
//    try {
//      $details = $this->mailUpAuthentication->getDetails($sms_gateway->id());
//      if ($details) {
//        $render['table']['company'][0]['#wrapper_attributes'] = ['header' => TRUE];
//        $render['table']['company'][0]['#plain_text'] = $this->t('Company');
//        $render['table']['company'][1]['#plain_text'] = $details['Company'];
//      }
//    }
//    catch (\Exception $e) {
//    }

    $render['table']['token'][0]['#wrapper_attributes'] = ['header' => TRUE];
    $render['table']['token'][0]['#plain_text'] = $this->t('Request token');
    $render['table']['token'][1] = [
      '#title' => $this->t('Request token'),
      '#type' => 'link',
      '#url' => Url::fromRoute('sms_mailup.gateway.oauth.token', ['sms_gateway' => $sms_gateway->id()]),
    ];

    return $render;
  }

  /**
   * Receive a new token.
   */
  public function receiveToken(Request $request, SmsGatewayInterface $sms_gateway) {
    if ($request->query->has('code') && $request->query->has('state')) {
      $gateway_id = $sms_gateway->id();
      $provider = $this->mailUpAuthentication->createOAuthProvider($gateway_id);

      $code = $request->query->get('code');
      $accessToken = $provider->getAccessToken('authorization_code', [
        'code' => $code,
      ]);

      $state = $request->query->get('state');
      $result = $accessToken->jsonSerialize();

      try {
        $this->mailUpAuthentication->setToken(
          $gateway_id,
          $state,
          $result['access_token'],
          $result['refresh_token'],
          $result['expires']
        );
        drupal_set_message($this->t('Successfully authenticated with Mailup API.'));

        $url = Url::fromRoute('sms_mailup.gateway.oauth', ['sms_gateway' => $gateway_id]);
        return new RedirectResponse($url->setAbsolute()->toString());
      }
      catch (\Exception $e) {
        drupal_set_message($this->t('Failed to set new token: %message', [
          '%message' => $e,
        ]), 'error');
      }
    }
    else {
      drupal_set_message($this->t('Missing parameters to create new token.'), 'error');
    }

    return [];
  }

}
