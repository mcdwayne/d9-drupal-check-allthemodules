<?php

/**
 * @file
 * Contains \Drupal\apiservices\Exception\EndpointDeniedException.
 */

namespace Drupal\apiservices\Exception;

/**
 * An exception thrown when there is an authentication failure in the API
 * endpoint response. The exception code may contain the specific cause.
 */
class EndpointDeniedException extends EndpointRequestException {}
