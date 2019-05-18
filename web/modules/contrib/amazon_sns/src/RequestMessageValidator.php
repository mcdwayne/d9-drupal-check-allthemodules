<?php

namespace Drupal\amazon_sns;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Validate inbound SNS messages from a Symfony Request object.
 */
class RequestMessageValidator {

  /**
   * Return a SNS message from an HTTP request.
   *
   * The AWS SDK only works with raw $_POST requests, which are more difficult
   * to mock for tests as compared to a Symfony Request.
   *
   * It is recommended that calling controllers catch \UnexpectedValueException,
   * \InvalidArgumentException, and
   * \Aws\Sns\Exception\InvalidSnsMessageException and cast them to an HTTP 4XX
   * class response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request containing the SNS message.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the request does not contain the SNS message type header.
   *
   * @return \Aws\Sns\Message
   *   The authenticated and validated SNS message.
   */
  public static function getMessageFromRequest(Request $request) {
    // Make sure the SNS-provided header exists.
    if (!isset($request->headers) || !$request->headers->get('X_AMZ_SNS_MESSAGE_TYPE')) {
      throw new \InvalidArgumentException('SNS message type header not provided');
    }

    // Ensure that the request body is valid JSON.
    $decoder = new JsonDecode(TRUE);
    $data = $decoder->decode($request->getContent(), JsonEncoder::FORMAT);

    // Validate the SNS message signature to protect against message injection.
    $message = new Message($data);
    $validator = new MessageValidator();
    $validator->validate($message);

    return $message;
  }

}
