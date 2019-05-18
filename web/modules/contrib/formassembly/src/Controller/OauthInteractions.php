<?php

namespace Drupal\formassembly\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\formassembly\ApiAuthorize;
use Drupal\formassembly\FormAssemblyKeyService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Fathershawn\OAuth2\Client\Provider\FormAssembly as OauthProvider;

/**
 * Utility class for interacting with FormAssembly for authorization.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2018 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @package Drupal\formassembly
 */
class OauthInteractions extends ControllerBase {

  /**
   * Injected service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Injected service.
   *
   * @var \Drupal\formassembly\ApiAuthorize
   */
  protected $apiAuthorize;


  /**
   * Injected service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Service to obtain oauth credentials.
   *
   * @var \Drupal\formassembly\FormAssemblyKeyService
   */
  protected $keyService;

  /**
   * OauthInteractions constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The injected requestStack service.
   * @param \Drupal\formassembly\ApiAuthorize $apiAuthorize
   *   The injected apiAuthorize service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The injected messenger service.
   * @param \Drupal\formassembly\FormAssemblyKeyService $keyService
   *   The injected key service.
   */
  public function __construct(RequestStack $requestStack,
                              ApiAuthorize $apiAuthorize,
                              Messenger $messenger,
                              FormAssemblyKeyService $keyService) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->apiAuthorize = $apiAuthorize;
    $this->messenger = $messenger;
    $this->keyService = $keyService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('formassembly.authorize'),
      $container->get('messenger'),
      $container->get('formassembly.key')
    );
  }

  /**
   * Assemble the authorization url and redirect to it.
   */
  public function authorize() {
    /** @var \Drupal\Core\Config\ImmutableConfig $formassembly_config */
    $credentials = $this->keyService->getOauthKeys();
    $provider = new OauthProvider([
      'clientId' => $credentials['cid'],
      'clientSecret' => $credentials['secret'],
      'redirectUri' => Url::fromRoute('fa_form.authorize.store', [],
        ['absolute' => TRUE])->toString(TRUE)->getGeneratedUrl(),
      'baseUrl' => $this->apiAuthorize->getUrl('base')->toString(TRUE)->getGeneratedUrl(),
    ]);
    $url = $provider->getAuthorizationUrl();
    $response = new TrustedRedirectResponse($url);
    $response->addCacheableDependency($url);
    return $response;
  }

  /**
   * Capture the authorization code and trigger token request.
   *
   * @throws \Exception
   *   Re-throws any caught exception after sending a message to the user.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the settings form.
   */
  public function code() {
    try {
      $code = $this->currentRequest->query->get('code');
      if (empty($code)) {
        throw new \UnexpectedValueException("The authorization_code query parameter is missing.");
      }
      $this->apiAuthorize->authorize($code);
      $this->messenger->addMessage($this->t('FormAssembly successfully authorized.'));
      $url = Url::fromRoute('fa_form.settings');
      return new RedirectResponse($url->toString());
    }
    catch (\Exception $exception) {
      $this->messenger->addMessage($this->t('FormAssembly module failed to authorize. Reason: @message', ['@message' => $exception->getMessage()]), 'error');
      throw $exception;
    }
  }

}
