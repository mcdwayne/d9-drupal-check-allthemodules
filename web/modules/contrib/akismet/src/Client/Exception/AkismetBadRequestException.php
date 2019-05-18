<?php

namespace Drupal\akismet\Client\Exception;

/**
 * Akismet error due to bad client request exception.
 *
 * Thrown in case the local time diverges too much from UTC.
 *
 * @see Akismet::TIME_OFFSET_MAX
 * @see Akismet::REQUEST_ERROR
 * @see Akismet::handleRequest()
 */
class AkismetBadRequestException extends AkismetException {
}
