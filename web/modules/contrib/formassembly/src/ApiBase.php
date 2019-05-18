<?php

namespace Drupal\formassembly;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Base class for Api Objects.
 *
 * Contains shared constants and the getUrl() method.
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
abstract class ApiBase {


  /**
   * The path for standard api requests.
   */
  const API_PATH = '/api_v1';

  /**
   * The path for admin api requests.
   */
  const ADMIN_API_PATH = '/admin/api_v1';

  /**
   * Flag reflecting formassembly.api.oauth.admin_index.
   *
   * @var bool
   */
  protected $isAdmin;

  /**
   * An array of url objects to the formassembly api endpoints.
   *
   * @var array
   */
  protected $endpoint;

  /**
   * Injected config service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Default logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ApiBase constructor. No matching create() method - used to build services.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Injected config service.
   * @param \Drupal\formassembly\LoggerInterface $loggerChannel
   *   Injected logging service.
   */
  public function __construct(ConfigFactory $config_factory, LoggerInterface $loggerChannel) {
    $this->configFactory = $config_factory;
    $this->logger = $loggerChannel;
    $config = $this->configFactory->get('formassembly.api.oauth');
    $this->isAdmin = $config->get('admin_index');
  }

  /**
   * Utility method to prepare a getUrl object.
   *
   * Segment values:
   *   - base
   *   - api
   *   - forms
   * Non-matching segments are passed through as arbitrary paths.
   *
   * @param string $segment
   *   A string key to indicate which path to use.
   *
   * @return \Drupal\Core\Url
   *   A url object configured for the proper endpoint.
   */
  public function getUrl($segment) {
    $options = [];
    // Init the path array.
    $paths = [
      'base' => '',
      'api' => $this->isAdmin ? $this::ADMIN_API_PATH : $this::API_PATH,
    ];
    // Define commonly used methods:
    $paths['forms'] = $paths['api'] . '/forms/index.json';
    $path = isset($paths[$segment]) ? $paths[$segment] : $segment;
    $oauth_config = $this->configFactory->get('formassembly.api.oauth');
    $base = $oauth_config->get('endpoint');
    return Url::fromUri($base . $path, $options);
  }

}
