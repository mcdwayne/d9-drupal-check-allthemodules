<?php

/**
 * @file
 * Contains \Drupal\apiservices\Exception\EndpointServerException.
 */

namespace Drupal\apiservices\Exception;

/**
 * An exception thrown when a request could not be completed due to an unknown
 * server error (HTTP 5xx codes).
 */
class EndpointServerException extends EndpointException {}
