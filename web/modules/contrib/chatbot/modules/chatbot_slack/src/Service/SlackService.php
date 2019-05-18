<?php

namespace Drupal\chatbot_slack\Service;

use Drupal\chatbot\Message\MessageInterface;
use Drupal\chatbot\Service\ServiceInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SlackService.
 *
 * @package Drupal\chatbot_slack\Workflow
 */
class SlackService implements ServiceInterface {

  const MESSAGE_TYPE_TEXT = 'text';
  const MESSAGE_TYPE_DECISION = 'decision';
  const MESSAGE_TYPE_POSTBACK = 'postback';
  const MESSAGE_TYPE_ATTACHMENT = 'attachment';
  const MESSAGE_TYPE_TEXT_OUT_LIMIT = 320;

  /**
   *
   * @var type string
   *  the url of the slack incoming webhook
   */
  private $incomingWebhookUrl;

  /**
   *
   * @var type string
   *  slack bot user id
   */
  private $userId;
  /**
   * The HTTP client to make calls to Slack with.
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
   * Constructs a SlackService.
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
   * Respond to Slack's challenge method.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function challenge() {
    $body = json_decode($this->request->getContent());
    $response = new JsonResponse(['challenge' => $body->challenge]);
    return $response;
  }

  /**
   * Helper function to unpack an array of Messages into independant items.
   *
   * @param array $messages
   *   An array of 1+ MessageInterface objects to send to the user.
   * @param string $channel_id
   *   The string channel id.
   */
  public function sendMessages(array $messages, $channel_id) {
    foreach ($messages as $message) {
      try {
        $this->sendMessage($message, $channel_id);
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
   * Send a Message to a Slack user.
   *
   * @param MessageInterface $message
   *   The formatted message body.
   * @param string $channel_id
   *   The string channel id.
   *
   * @return bool
   *   The request status.
   */
  public function sendMessage(MessageInterface $message, $channel_id) {
    if (!isset($this->incomingWebhookUrl)) {
      $this->logger->error("Slack Service is not properly configured to send messages.");
      return FALSE;
    }

    $formatted_message = $message->getFormattedMessage();
    $formatted_message->type = 'message';
    $formatted_message->channel = $channel_id;

    $messageSendingURL = $this->incomingWebhookUrl;
    $client = $this->httpClient;
    try {
      $client->post($messageSendingURL, [
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
   * Translate json from the Slack API and group by user ID.
   *
   * @param string $rawData
   *   Json encoded data from the Slack API.
   *
   * @return array
   *   A multidimensional array of user messages, keyed by user id.
   *
   * @throws \Exception
   *   Thrown if the array key 'entry' is not present.
   */
  public function translateRequest($rawData) {
    if (!isset($this->userId)) {
      $this->logger->error("Slack Service is not properly configured to parse messages.");
      return [];
    }

    $rawData = $this->normalizeData($rawData);

    $messages = [];

    if (!is_array($rawData)) {
      throw new \Exception('Unable to parse data due to unexpected structure');
    }

    // if user name matches the chatbot - then we ignore the message.
    if (!isset($rawData['user_id']) || $rawData['user_id'] == $this->userId) {
      return $messages;
    }

    // ignore messages sent by bots
    if (isset($rawData['bot_id'])) {
      return $messages;
    }

    // use channel id as uid
    $uid = $rawData['channel_id'];
    $messageType = self::typeFromMessage($rawData);
    $messageContent = self::contentFromMessage($rawData);

    // Do not continue if uid, type or content could not be determined.
    if (!$messageType || !$messageContent || !$uid) {
      $this->logger->error('Omitting message due to unexpected structure.');
      return $messages;
    }

    $messages[$uid] = isset($messages[$uid]) ? $messages[$uid] : [];
    $messages[$uid][] = [
      'message_type' => $messageType,
      'message_content' => $messageContent,
      'user_name' => $rawData['user_name'],
      'user_id' => $rawData['user_id'],
      'channel_name' => $rawData['channel_name'],
    ];
    return $messages;
  }

  private function normalizeData($data) {

    if (is_array($data) && isset($data['payload'])) {
      $data = $this->normalizePayload($data);
    } elseif (is_object($data) && isset($data->event)) {
      $data = $this->normalizeEventData($data);
    }

    return $data;
  }

  private function normalizePayload($dataArray) {
    $payloadObject = json_decode($dataArray['payload']);

    if (!isset($payloadObject->user)) {
      return [];
    }

    $data_array = [];
    $data_array['user_id'] = $payloadObject->user->id;
    $data_array['user_name'] = $payloadObject->user->name;
    $data_array['channel_id'] = $payloadObject->channel->id;
    $data_array['channel_name'] = $payloadObject->channel->name;
    $data_array['response_url'] = $payloadObject->response_url;
    $data_array['type'] = "event_callback";
    $data_array['payload'] = $payloadObject->actions[0]->value;

    return $data_array;
  }

  private function normalizeEventData($dataObject) {
    if (!isset($dataObject->event->user)) {
      return [];
    }

    $data_array = [];
    $data_array['user_id'] = $dataObject->event->user;
    $data_array['user_name'] = $dataObject->event->user;
    $data_array['channel_id'] = $dataObject->event->channel;
    $data_array['channel_name'] = $dataObject->event->channel;
    $data_array['type'] = $dataObject->event->type;
    $data_array['text'] = $dataObject->event->text;

    return $data_array;
  }

  /**
   * Get a user's FB info given a user ID and fields to retrieve from FB.
   *
   * @param $userID
   *  The Slack User ID.
   * @param array $fieldsToRetrieve
   *  The fields to retrieve from Slack pertaining to the passed userID.
   * @return array|void
   *  The requested fields from Slack or null in the case of a request error.
   */
  public function getUserInfo($userID, array $fieldsToRetrieve = array()) {
    // Build user info array to return to user.
    $userInfo = [];

    return $userInfo;
  }

  /**
   * Helper function to Log JSON error object received from Slack.
   *
   * @param $response
   *  Error object received from Slack.
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
    if (isset($configuration['slack_incoming_webhook_url']) && isset($configuration['slack_user_id'])) {
      $this->incomingWebhookUrl = $configuration['slack_incoming_webhook_url'];
      $this->userId = $configuration['slack_user_id'];
      return TRUE;
    }
    return FALSE;
  }

  public static function contentFromMessage(array $message) {
    switch (self::typeFromMessage($message)) {
      case self::MESSAGE_TYPE_TEXT:
        $content = $message['text'];
        break;

      case self::MESSAGE_TYPE_POSTBACK:
        $content = $message['payload'];
        break;

      default:
        $content = FALSE;
    }

    return $content;
  }

  public static function splitTextMessage($message, $startPosition = 0) {
    $messageParts = [];
    return $messageParts;
  }

  public static function typeFromMessage($message) {
    $messageType = FALSE;
    if (isset($message['type']) && $message['type'] == "message") {
      $messageType = self::MESSAGE_TYPE_TEXT;
    }
    if (isset($message['type']) && $message['type'] == "event_callback") {
      $messageType = self::MESSAGE_TYPE_POSTBACK;
    }

    if (isset($message['payload'])) {
      $messageType = self::MESSAGE_TYPE_POSTBACK;
    }

    return $messageType;
  }

}
