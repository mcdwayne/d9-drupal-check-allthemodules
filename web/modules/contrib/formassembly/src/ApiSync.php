<?php

namespace Drupal\formassembly;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\formassembly\Exception\FormAssemblyException;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Service class for FormAssembly API: Handles form sync.
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
class ApiSync extends ApiBase {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * FormAssembly storage..
   *
   * @var \Drupal\formassembly\FormAssemblyStorage
   */
  protected $faStorage;

  /**
   * Injected authorization service.
   *
   * @var \Drupal\formassembly\ApiAuthorize
   */
  protected $authorize;

  /**
   * Injected service for fasync cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Form data obtained from FormAssembly.
   *
   * @var array
   */
  private $forms;

  /**
   * Current page number if using admin index.
   *
   * @var int
   */
  private $page;

  /**
   * Hash of the last response.
   *
   * @var string
   */
  private $lastHash;

  /**
   * ApiSync constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Injected config service.
   * @param \Psr\Log\LoggerInterface $loggerChannel
   *   Injected service.
   *   Injected service.
   * @param \GuzzleHttp\Client $http_client
   *   Injected Guzzle client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Injected entity type service.
   * @param \Drupal\formassembly\ApiAuthorize $authorize
   *   Injected api service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Injected service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ConfigFactory $config_factory,
    LoggerInterface $loggerChannel,
    Client $http_client,
    EntityTypeManagerInterface $entity_type_manager,
    ApiAuthorize $authorize,
    CacheBackendInterface $cacheBackend
  ) {
    parent::__construct($config_factory, $loggerChannel);
    $this->httpClient = $http_client;
    $this->faStorage = $entity_type_manager->getStorage('fa_form');
    $this->authorize = $authorize;
    $this->cache = $cacheBackend;
    $this->forms = [];
    $this->page = 1;
    $this->lastHash = '';
  }

  /**
   * Get data about available forms.
   *
   * @param string $id
   *   The id to use as a cache id.
   *
   * @return array
   *   An array of objects representing FormAssembly forms.
   */
  public function getForms($id = '') {
    /*
     * We need to cache forms and page count to facilitate batch processing.
     * So, see if there is a stored cache object with this ID.
     */
    $this->loadFromCache($id);
    try {
      if ($this->isAdmin) {
        $finished = $this->getAdminForms();
      }
      else {
        $finished = $this->getStandardForms();
      }
      $this->setCache($id);
      return $finished;
    }
    catch (\Exception $e) {
      $this->logger->critical(
        'FormAssembly getForms request failed with Exception: %exception_type.',
        ['%exception_type' => get_class($e)]
      );
      throw $e;
    }
  }

  /**
   * Helper method to load a set of form and page count from cache.
   *
   * @param string $id
   *   The cache id.
   */
  protected function loadFromCache($id) {
    if (empty($id)) {
      // Nothing to cache.
      return;
    }
    $data = $this->cache->get($id);
    if (!empty($data)) {
      $this->forms = $data->data['forms'];
      $this->page = $data->data['page'];
      $this->lastHash = $data->data['hash'];
    }
  }

  /**
   * Helper method to store a set of form and page count in cache.
   *
   * @param string $id
   *   The cache id.
   */
  protected function setCache($id) {
    if (empty($id)) {
      // Nothing to retrieve.
      return;
    }
    $data = [
      'forms' => $this->forms,
      'page' => $this->page,
      'hash' => $this->lastHash,
    ];
    // We will clean up after ourselves in syncForms() but set a 24 hour expire
    // to guard against ever growing cache bin.
    $this->cache->set($id, $data, strtotime('+1 day'));
  }

  /**
   * Helper method to clear a set of form and page count from cache.
   *
   * @param string $id
   *   The cache id.
   */
  protected function clearCache($id) {
    if (empty($id)) {
      // Nothing to clear.
      return;
    }
    $this->cache->delete($id);
  }

  /**
   * Helper method for getting forms from the admin index.
   *
   * @throws \Drupal\formassembly\Exception\FormAssemblyException
   *   If the request is not returned in proper form.
   * @throws \UnexpectedValueException
   *   If an http return code other than 200 is received.
   * @throws \Exception
   *   Any exception passed through from getting a token or the request.
   */
  protected function getAdminForms() {
    $finished = FALSE;
    $url = $this->getUrl('forms');
    $url->setOptions(
      [
        'query' => [
          'access_token' => $this->authorize->getToken(),
          'show' => 50,
          'page' => $this->page,
        ],
      ]
    );
    $request = $this->httpClient->get($url->toString(TRUE)->getGeneratedUrl());
    // Guzzle throws an Exception on http 400/500 errors.
    // Ensure we have a 200.
    if ($request->getStatusCode() != 200) {
      throw new \UnexpectedValueException(
        'Http return code 200 expected.  Code ' . $request->getStatusCode() . ' received.'
      );
    }
    $raw = $request->getBody()->getContents();
    // Hash request to see if it has changed.
    $current_hash = sha1($raw);
    if ($this->lastHash === $current_hash || empty($raw)) {
      // Observed behavior from formassembly is if n pages are needed to
      // iterate the admin index page n+1 returns the same response as page n.
      // But formassembly advises this could change to page n+1 returns empty.
      // Neither of these responses should be processed and we are finished.
      $finished = TRUE;
      $this->page = 1;
    }
    else {
      $this->lastHash = $current_hash;
      // Process response:
      $response = json_decode($raw, TRUE);
      // If we don't get JSON there is an error.
      if (json_last_error() != JSON_ERROR_NONE) {
        throw new FormAssemblyException(
          'The message body ' . $request->getBody() . ' is not JSON'
        );
      }
      $this->processResponse($response);
      // Increment page:
      ++$this->page;
    }
    return $finished;
  }

  /**
   * Helper method for getting forms from the standard index.
   *
   * @throws \Drupal\formassembly\Exception\FormAssemblyException
   *   If the request is not returned in proper form.
   * @throws \UnexpectedValueException
   *   If an http return code other than 200 is received.
   * @throws \Exception
   *   Any exception passed through from getting a token or the request.
   */
  protected function getStandardForms() {
    $url = $this->getUrl('forms');
    $url->setOptions(
      [
        'query' => [
          'access_token' => $this->authorize->getToken(),
        ],
      ]
    );
    $request = $this->httpClient->get($url->toUriString());
    // Guzzle throws an Exception on http 400/500 errors.
    // Ensure we have a 200.
    if ($request->getStatusCode() != 200) {
      throw new \UnexpectedValueException(
        'Http return code 200 expected.  Code ' . $request->getStatusCode() . ' received.'
      );
    }
    // Process response:
    $response = json_decode($request->getBody(), TRUE);
    // If we don't get JSON there is an error.
    if (json_last_error() != JSON_ERROR_NONE) {
      throw new FormAssemblyException(
        'The message body ' . $request->getBody() . ' is not JSON'
      );
    }
    $this->processResponse($response);
    return TRUE;
  }

  /**
   * Helper method to process responses property into forms array.
   *
   * @param array $response
   *   Associative array from json_decode.
   */
  protected function processResponse(array $response) {
    if (!empty($response['Forms'])) {
      $this->forms = array_merge($this->forms, $response['Forms']);
    }
    if (!empty($response['Category'])) {
      $this->extractCategories($response['Category']);
    }
  }

  /**
   * Helper method for extracting forms nested within Categories.
   *
   * @param array $category
   *   A Category array - may contain additional Category arrays.
   */
  protected function extractCategories(array $category) {
    foreach ($category as $formset) {
      if (!empty($formset['Category'])) {
        $this->extractCategories($formset['Category']);
      }
      if (!empty($formset['Forms'])) {
        $this->forms = array_merge($this->forms, $formset['Forms']);

      }
    }
  }

  /**
   * Process form data from FormAssembly and perform CRUD ops as needed.
   *
   * @param string $id
   *   The id to use as a cache id.
   */
  public function syncForms($id = '') {
    $this->loadFromCache($id);
    try {
      $formsByFaId = [];
      foreach ($this->forms as $formData) {
        $formsByFaId[$formData['Form']['id']] = [
          'faid' => $formData['Form']['id'],
          'name' => Xss::filter(
            Html::decodeEntities($formData['Form']['name'])
          ),
          'modified' => date('U', strtotime($formData['Form']['modified'])),
        ];
      }
      // Update forms that have changed since the last sync.
      foreach ($formsByFaId as $formData) {
        // Load an existing FormAssembly entity if one matches
        // $formData['faid'] or create a new entity otherwise.
        $searchByFaId = $this->faStorage
          ->loadByProperties(['faid' => $formData['faid']]);
        // The search returns data as an array keyed by eid or an empty array if
        // no match.
        if (!empty($searchByFaId)) {
          // There should only be one item - faid is a unique key so pop
          // the first item off the array.
          /** @var \Drupal\formassembly\Entity\FormAssemblyEntity $formAssemblyEntity */
          $formAssemblyEntity = array_shift($searchByFaId);
          // Update forms that have changed since the last sync.
          if ($formAssemblyEntity->getModifiedTime() < $formData['modified']) {
            // Update the title and modified date stored.
            $formAssemblyEntity->setModifiedTime($formData['modified']);
            $formAssemblyEntity->setName($formData['name']);
            $formAssemblyEntity->enable();
            $formAssemblyEntity->save();
          }
        }
        else {
          $newEntity = $this->faStorage->create($formData);
          $newEntity->save();
        }
      }
      $this->faStorage->disableInactive(array_keys($formsByFaId));
      $this->logger->info('Form sync complete.');
      // Clean up any cached data.
      $this->clearCache($id);
    }
    catch (\Exception $e) {
      $this->logger->critical(
        'FormAssembly syncForms request failed with Exception: %exception_type.',
        [
          '%exception_type' => get_class($e),
        ]
      );
      throw $e;
    }
  }

  /**
   * Getter method for the page property.
   *
   * @return int
   *   The current page number.
   */
  public function getPage() {
    return $this->page;
  }

}
