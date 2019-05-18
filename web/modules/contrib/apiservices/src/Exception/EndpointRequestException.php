<?php

/**
 * @file
 * Contains \Drupal\apiservices\Exception\EndpointRequestException.
 */

namespace Drupal\apiservices\Exception;

/**
 * An exception thrown when a request could not be completed because the client
 * sent a malformed request (HTTP 4xx codes).
 */
class EndpointRequestException extends EndpointException {}
