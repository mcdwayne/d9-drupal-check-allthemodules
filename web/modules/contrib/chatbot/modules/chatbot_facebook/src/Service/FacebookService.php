<?php

namespace Drupal\chatbot_facebook\Service;

use Drupal\chatbot\Message\MessageInterface;
use Drupal\chatbot\Service\ServiceInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception;
use Drupal\Component\Utility\Html;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FacebookService.
 *
 * @package Drupal\chatbot_facebook\Service
 */
class FacebookService implements ServiceInterface {

  const MESSAGE_TYPE_TEXT = 'text';
  const MESSAGE_TYPE_POSTBACK = 'postback';
  const MESSAGE_TYPE_ATTACHMENT = 'attachment';
  const MESSAGE_TYPE_TEXT_OUT_LIMIT = 320;


  private $apiURL;
  private $verifyToken;
  private $pageAccessToken;

  /**
   * The HTTP client to make calls to Facebook with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request $request
   */
  public $request;

  /**
   * Constructs a FacebookService.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   A Guzzle client object.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ClientInterface $httpClient, LoggerInterface $logger, RequestStack $request) {
    $this->httpClient = $httpClient;
    $this->logger = $logger;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * Respond to Facebook's challenge method.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function challenge() {
    if (!isset($this->apiURL) || !isset($this->verifyToken) || !isset($this->pageAccessToken)) {
      $response = new Response('Error, service not configured properly.');
      $this->logger->error("Facebook Service is not properly configured to respond for challenge method.");
      return $response;
    }

    $request_query = $this->request->query;

    // Get verify token and challenge we expect Facebook to send in the request.
    $verify_token = $request_query->get('hub_verify_token');
    $challenge = $request_query->get('hub_challenge');

    // If the tokens match, respond to Facebook with the challenge they sent.
    if ($verify_token === $this->verifyToken) {
      $response = new Response($challenge);
    }
    else {
      $response = new Response('Error, wrong verification token');
      $this->logger->notice("The verification token received (" . $verify_token . ") does not match the one stored in settings (" . $this->verifyToken . ")");
    }
    return $response;
  }

  /**
   * Helper function to unpack an array of Messages into independant items.
   *
   * @param array $messages
   *   An array of 1+ MessageInterface objects to send to the user.
   * @param string $userID
   *   The string user id.
   */
  public function sendMessages(array $messages, $userID) {
    foreach ($messages as $message) {
      try {
        $this->sendMessage($message, $userID);
      }
      catch (\Exception $e) {
        $loggerVariables = [
          '@exception_message' => $e->getMessage(),
        ];
        $this->logger->error('sendMessage returned and error. Exception: @exception_message', $loggerVariables);
      }
    }

  }

  /**
   * Send a Message to a Facebook Messenger user.
   *
   * @param MessageInterface $message
   *   The formatted message body.
   * @param string $user_id
   *   String user id.
   *
   * @return bool
   *   The request status.
   */
  public function sendMessage(MessageInterface $message, $user_id) {
    if (!isset($this->apiURL) || !isset($this->verifyToken) || !isset($this->pageAccessToken)) {
      $this->logger->error("Facebook Service is not properly configured to send messages.");
      return FALSE;
    }
    $formatted_message = [
      'recipient' => [
        'id' => $user_id,
      ],
      'message' => $message->getFormattedMessage(),
    ];

    $messageSendingURL = $this->apiURL . 'me/messages?access_token=' . $this->pageAccessToken;
    $client = $this->httpClient;
    try {
      $request = $client->post($messageSendingURL, [
        'json' => $formatted_message,
      ]);
      return TRUE;
    }
    catch (Exception\RequestException $e) {
      $rawResponse = $e->getResponse()->getBody();
      $response = Json::decode($rawResponse);
      if (empty($response)) {
        $loggerVariables = [
          '@exception_message' => $e->getMessage(),
        ];
        $this->logger->error('Send API error: Exception: @exception_message', $loggerVariables);
      }
      // Facebook sent back an error.
      elseif (array_key_exists('error', $response)) {
        $this->logServiceErrorResponse($response);
      }
      return FALSE;
    }
    catch (\Exception $e) {
      $loggerVariables = [
        '@exception_message' => $e->getMessage(),
      ];
      $this->logger->error('Send API error: Exception: @exception_message', $loggerVariables);
      return FALSE;
    }
  }

  /**
   * Translate json from the Facebook API and group by user ID.
   *
   * @param string $rawData
   *   Json encoded data from the Facebook API.
   *
   * @return array
   *   A multidimensional array of user messages, keyed by user id.
   *
   * @throws \Exception
   *   Thrown if the array key 'entry' is not present.
   */
  public function translateRequest($rawData) {
    $messages = [];
    $data = Json::decode($rawData);

    // Ensure the expected 'entry' key is in the array.
    if (!is_array($data) || !array_key_exists('entry', $data)) {
      throw new \Exception('Unable to parse data due to unexpected structure');
    }

    foreach ($data['entry'] as $entry) {
      // @todo: Should timestamp or page_id be attached to the messages array?
      foreach ($entry['messaging'] as $message) {
        $uid = $message['sender']['id'];
        $messageType = self::typeFromMessage($message);
        $messageContent = self::contentFromMessage($message);

        // Do not continue if uid, type or content could not be determined.
        if (!$messageType || !$messageContent || !$uid) {
          $this->logger->error('Omitting message due to unexpected structure.');
          continue;
        }

        $messages[$uid] = isset($messages[$uid]) ? $messages[$uid] : [];
        $messages[$uid][] = [
          'message_type' => $messageType,
          'message_content' => $messageContent,
        ];
      }
    }

    return $messages;
  }

  /**
   * Determine message type from array structure.
   *
   * @param array $message
   *   The value of the 'messaging' key from a facebook API event.
   *
   * @return bool|string
   *   The message type, or FALSE if none of the valid array keys was found.
   */
  public static function typeFromMessage($message) {
    $messageType = FALSE;
    if (isset($message['message']['text'])) {
      $messageType = self::MESSAGE_TYPE_TEXT;
    }
    elseif (isset($message['postback'])) {
      $messageType = self::MESSAGE_TYPE_POSTBACK;
    }
    elseif (isset($message['message']['attachments'])) {
      $messageType = self::MESSAGE_TYPE_ATTACHMENT;
    }

    return $messageType;
  }

  /**
   * Return the message content, based on the message type.
   *
   * @param array $message
   *   The value of the 'messaging' key from a facebook API event.
   *
   * @return mixed
   *   The message content, or FALSE if no valid array key was found.
   */
  public static function contentFromMessage(array $message) {
    switch (self::typeFromMessage($message)) {
      case self::MESSAGE_TYPE_TEXT:
        $content = $message['message']['text'];
        break;

      case self::MESSAGE_TYPE_ATTACHMENT:
        $content = $message['message']['attachments'];
        break;

      case self::MESSAGE_TYPE_POSTBACK:
        $content = $message['postback']['payload'];
        break;

      default:
        $content = FALSE;
    }

    return $content;
  }

  /**
   * Return an array of the passed string split into sizes within FB's outgoing limit.
   *
   * @param string $message
   *   A string which may be longer than FB's outgoing message limit.
   *
   * @return mixed
   *   An array of decoded strings which are within FB's outgoing limit message size.
   */
  public static function splitTextMessage($message, $startPosition = 0) {
    $maxLength = self::MESSAGE_TYPE_TEXT_OUT_LIMIT;
    $messageParts = array();
    $message = Html::decodeEntities(trim($message), ENT_QUOTES);
    $messagePart = substr($message, $startPosition, $maxLength);

    if (strlen($message) > ($startPosition + $maxLength)) {
      $whiteSpaceMatches = preg_match('/.*\s([^\s]+)$/', $messagePart, $matches);
      $trimLength = 0;
      if (!empty($matches[1])) {
        if (strlen($matches[1]) < strlen($messagePart)) {
          $trimLength = strlen($matches[1]);
          $maxLength = $maxLength - $trimLength;
          $messagePart = substr($message, $startPosition, $maxLength);
        }
      }
    }
    $messageParts[] = trim($messagePart);
    if (strlen($message) > ($startPosition + $maxLength)) {
      $messageParts = array_merge($messageParts, self::splitTextMessage($message, $startPosition + $maxLength));
    }
    return $messageParts;
  }

  /**
   * Get a user's FB info given a user ID and fields to retrieve from FB.
   *
   * @param $userID
   *  The Facebook User ID.
   * @param array $fieldsToRetrieve
   *  The fields to retrieve from Facebook pertaining to the passed userID.
   * @return array|void
   *  The requested fields from Facebook or null in the case of a request error.
   */
  public function getUserInfo($userID, array $fieldsToRetrieve = ['first_name','last_name']) {
    if (!isset($this->apiURL) || !isset($this->verifyToken) || !isset($this->pageAccessToken)) {
      $this->logger->error("Facebook Service is not properly configured to retrieve user info.");
      return;
    }
    $userProfileApi = $this->apiURL . $userID;
    $fieldsAsQueryString = implode(",", $fieldsToRetrieve);
    $query_string = array(
      'fields' => $fieldsAsQueryString,
      'access_token' => $this->pageAccessToken,
    );

    // Request to User Profile API.
    $client = $this->httpClient;
    try {
      $request = $client->get($userProfileApi, [
        'query' => $query_string,
      ]);
      $rawResponse = $request->getBody();
    }

    catch (Exception\RequestException $e) {
      $rawResponse = $e->getResponse()->getBody();
      $response = Json::decode($rawResponse);
      // Not a json-formatted response like we expected.
      if (empty($response)) {
        $loggerVariables = [
          '@exception_message' => $e->getMessage(),
        ];
        $this->logger->error('User Profile API error: Exception: @exception_message.', $loggerVariables);
      }
      // Facebook sent back an error.
      elseif (array_key_exists('error', $response)) {
        $this->logServiceErrorResponse($response, 'User Profile API');
      }
      return;
    }
    catch (\Exception $e) {
      $loggerVariables = [
        '@exception_message' => $e->getMessage(),
      ];
      $this->logger->error('User Profile API error: Exception: @exception_message.', $loggerVariables);
      return;
    }

    // Haven't decoded the $raw_response yet, so decode now.
    if (empty($response)) {
      $response = Json::decode($rawResponse);
    }

    // Build user info array to return to user.
    $userInfo = [];
    foreach ($response as $field => $fieldValue) {
      $userInfo[$field] = $fieldValue;
    }
    return $userInfo;
  }

  /**
   * Helper function to Log JSON error object received from Facebook.
   *
   * @param $response
   *  Error object received from Facebook.
   * @param string $api
   *  API we were using when we received the error.
   */
  public function logServiceErrorResponse($response, $api = 'Send API') {
    $message = isset($response['error']['message']) ? $response['error']['message'] : '';
    $type = isset($response['error']['type']) ? $response['error']['type'] : '';
    $code = isset($response['error']['code']) ? $response['error']['code'] : '';
    $loggerVariables = [
      '@api' => $api,
      '@message' => $message,
      '@type' => $type,
      '@code' => $code,
    ];
    $this->logger->error('@api error: @message. Type: @type. Code: @code.',
      $loggerVariables
    );
  }

  /**
   * Pass configuration to the service.
   *
   * @param array $configuration
   *
   * @return bool
   */
  public function configure(array $configuration) {
    if (!isset($configuration['fb_api_url']) || !isset($configuration['fb_verify_token']) ||
        !isset($configuration['fb_page_access_token'])) {
      return FALSE;
    }

    $this->apiURL = $configuration['fb_api_url'];
    $this->verifyToken = $configuration['fb_verify_token'];
    $this->pageAccessToken = $configuration['fb_page_access_token'];

    return TRUE;
  }

}
