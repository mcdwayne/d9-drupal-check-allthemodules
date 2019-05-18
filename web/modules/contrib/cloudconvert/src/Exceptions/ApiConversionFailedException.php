<?php

namespace Drupal\cloudconvert\Exceptions;

/**
 * ApiConversionFailedException exception is thrown.
 *
 * When a the CloudConvert API returns any HTTP error code 422.
 *
 * @package CloudConvert
 * @category Exceptions
 * @author Josias Montag <josias@montag.info>
 */
class ApiConversionFailedException extends ApiException {

}
