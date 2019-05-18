<?php

namespace Drupal\formassembly;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Drupal\formassembly\Entity\FormAssemblyEntity;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Service class for FormAssembly API: Fetches form markup.
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
class ApiMarkup extends ApiBase {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Injected authorization service.
   *
   * @var \Drupal\formassembly\ApiAuthorize
   */
  protected $authorize;

  /**
   * Injected service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Injected service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokens;

  /**
   * Injected Service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Injected Service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $userProxy;

  /**
   * ApiSync constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Injected config service.
   * @param \Psr\Log\LoggerInterface $loggerChannel
   *   Injected service.
   * @param \GuzzleHttp\Client $http_client
   *   Injected Guzzle client.
   * @param \Drupal\formassembly\ApiAuthorize $authorize
   *   Injected api service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Injected service.
   * @param \Drupal\Core\Utility\Token $tokenService
   *   Injected service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Injected service.
   * @param \Drupal\Core\Session\AccountProxyInterface $userProxy
   *   Injected service.
   */
  public function __construct(
    ConfigFactory $config_factory,
    LoggerInterface $loggerChannel,
    Client $http_client,
    ApiAuthorize $authorize,
    ModuleHandlerInterface $moduleHandler,
    Token $tokenService,
    RouteMatchInterface $routeMatch,
    AccountProxyInterface $userProxy
  ) {
    parent::__construct($config_factory, $loggerChannel);
    $this->httpClient = $http_client;
    $this->authorize = $authorize;
    $this->moduleHandler = $moduleHandler;
    $this->tokens = $tokenService;
    $this->routeMatch = $routeMatch;
    $this->userProxy = $userProxy;
  }

  /**
   * Retrieve the HTML for a FormAssembly form.
   *
   * The FA API recognizes query parameters passed on the rest URL and will
   * use them to pre-fill fields in the returned form markup.  Here we fold
   * in parameters configured via formassembly_form() and expose the hook
   * hook_formassembly_form_params_alter(&$params) to allow modules to modify
   * the passed parameter list.
   *
   * @param \Drupal\formassembly\Entity\FormAssemblyEntity $entity
   *   Entity form object.
   *
   * @return string
   *   HTML representation of the form.
   */
  public function getFormMarkup(FormAssemblyEntity $entity) {
    $params = [];
    if (!$entity->query_params->isEmpty()) {
      $params = $entity->query_params->value;
    }
    // Expose hook_formassembly_form_params_alter()
    $this->moduleHandler->alter('formassembly_form_params', $params);
    // Replace any tokens found in the parameter pair values.
    $data = [
      'user' => $this->userProxy->getAccount(),
    ];
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof ContentEntityInterface) {
        $data[$parameter->getEntityTypeId()] = $parameter;
      }
    }
    foreach ($params as $key => $value) {
      $params[$key] = $this->tokens->replace($value, $data);
    }
    // Make FA rest call and return form markup.
    $url = $this->getUrl('/rest/forms/view/' . $entity->faid->value);
    $url->setOptions(['query' => $params]);
    $request = $this->httpClient->get($url->toString(TRUE)->getGeneratedUrl());
    // Guzzle throws an Exception on http 400/500 errors.
    // Ensure we have a 200.
    if ($request->getStatusCode() != 200) {
      throw new \UnexpectedValueException(
        'Http return code 200 expected.  Code ' . $request->getStatusCode() . ' received.'
      );
    }
    return $request->getBody()->getContents();
  }

  /**
   * Get the HTML for a FormAssembly next path using returned tfa_next value.
   *
   * @param string $tfa_next
   *   The urlencoded parameter from FormAssembly.
   *
   * @return string
   *   HTML returned by the query
   */
  public function getNextForm($tfa_next) {
    $queryPath = urldecode($tfa_next);
    // Make FA rest call and return form markup.
    $url = $this->getUrl('/rest/' . $queryPath);
    $request = $this->httpClient->get($url->toString(TRUE)->getGeneratedUrl());
    // Guzzle throws an Exception on http 400/500 errors.
    // Ensure we have a 200.
    if ($request->getStatusCode() != 200) {
      throw new \UnexpectedValueException(
        'Http return code 200 expected.  Code ' . $request->getStatusCode() . ' received.'
      );
    }
    return $request->getBody()->getContents();
  }

}
