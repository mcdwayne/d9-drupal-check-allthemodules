<?php

namespace Drupal\chatbot\Service;

use Drupal\chatbot\Message\MessageInterface;

interface ServiceInterface {

  /**
   * Pass configuration to the service.
   *
   * @param array $configuration
   *
   * @return bool
   */
  public function configure(array $configuration);

  /**
   * Respond to Service's challenge method.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function challenge();

  /**
   * Helper function to unpack an array of Messages into independant items.
   *
   * @param array $messages
   *   An array of 1+ MessageInterface objects to send to the user.
   * @param string $id
   *   Channel or user id.
   */
  public function sendMessages(array $messages, $id);

  /**
   * Send a Message to a Service user.
   *
   * @param MessageInterface $message
   *   The formatted message body.
   * @param string $id
   *   Channel or user id.
   *
   * @return bool
   *   The request status.
   */
  public function sendMessage(MessageInterface $message, $id);

  /**
   * Translate json from the Service API and group by user ID.
   *
   * @param string $rawData
   *   Json encoded data from the Service API.
   *
   * @return array
   *   A multidimensional array of user messages, keyed by user id.
   *
   * @throws \Exception
   *   Thrown if the array key 'entry' is not present.
   */
  public function translateRequest($rawData);

  /**
   * Determine message type from array structure.
   *
   * @param array $message
   *   The value of the 'messaging' key from a Service API event.
   *
   * @return bool|string
   *   The message type, or FALSE if none of the valid array keys was found.
   */
  public static function typeFromMessage($message);

  /**
   * Return the message content, based on the message type.
   *
   * @param array $message
   *   The value of the 'messaging' key from a Service API event.
   *
   * @return mixed
   *   The message content, or FALSE if no valid array key was found.
   */
  public static function contentFromMessage(array $message);

  /**
   * Return an array of the passed string split into sizes within Service's outgoing limit.
   *
   * @param string $message
   *   A string which may be longer than Service's outgoing message limit.
   *
   * @return mixed
   *   An array of decoded strings which are within Service's outgoing limit message size.
   */
  public static function splitTextMessage($message, $startPosition = 0);

  /**
   * Get a user's Service info given a user ID and fields to retrieve from Service.
   *
   * @param $userID
   *  The Service User ID.
   * @param array $fieldsToRetrieve
   *  The fields to retrieve from Service pertaining to the passed userID.
   * @return array|void
   *  The requested fields from Service or null in the case of a request error.
   */
  public function getUserInfo($userID, array $fieldsToRetrieve = ['first_name','last_name']);

  /**
   * Helper function to Log JSON error object received from Service.
   *
   * @param $response
   *  Error object received from Service.
   * @param string $api
   *  API we were using when we received the error.
   */
  public function logServiceErrorResponse($response, $api = 'Send API');

}
