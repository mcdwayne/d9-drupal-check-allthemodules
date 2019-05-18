<?php

namespace Drupal\slack;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Send messages to Slack.
 */
class Slack {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $logger;

  /**
   * Constructs a Slack object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Module configuration.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   HTTP Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger.
   */
  public function __construct(ConfigFactoryInterface $config, ClientInterface $http_client, LoggerChannelFactoryInterface $logger) {
    $this->config = $config;
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * Send message to the Slack.
   *
   * @param string $message
   *   The message sent to the channel.
   * @param string $channel
   *   The channel in the Slack service to send messages.
   * @param string $username
   *   The bot name displayed in the channel.
   *
   * @return bool|object
   *   Slack response.
   */
  public function sendMessage($message, $channel = '', $username = '') {
    $config = $this->config->get('slack.settings');
    $webhook_url = $config->get('slack_webhook_url');

    if (empty($webhook_url)) {
      $this->messenger()->addError($this->t('You need to enter a webhook!'));
      return FALSE;
    }

    $this->logger->get('slack')
      ->info('Sending message "@message" to @channel channel as "@username"', [
        '@message' => $message,
        '@channel' => $channel,
        '@username' => $username,
      ]);

    $config = $this->prepareMessage($webhook_url, $channel, $username);
    $result = $this->sendRequest(
      $config['webhook_url'], $message, $config['message_options']
    );

    return $result;
  }

  /**
   * Prepare message meta fields for Slack.
   *
   * @param string $webhook_url
   *   Webhook for Slack basic functions.
   * @param string $channel
   *   The channel in the Slack service to send messages.
   * @param string $username
   *   The bot name displayed in the channel.
   *
   * @return array
   *   Config array.
   */
  protected function prepareMessage($webhook_url, $channel, $username) {
    $config = $this->config->get('slack.settings');
    $message_options = [];

    if (!empty($channel)) {
      $message_options['channel'] = $channel;
    }
    elseif (!empty($config->get('slack_channel'))) {
      $message_options['channel'] = $config->get('slack_channel');
    }

    if (!empty($username)) {
      $message_options['username'] = $username;
    }
    elseif (!empty($config->get('slack_username'))) {
      $message_options['username'] = $config->get('slack_username');
    }
    $icon_type = $config->get('slack_icon_type');

    if ($icon_type == 'emoji') {
      $message_options['icon_emoji'] = $config->get('slack_icon_emoji');
    }
    elseif ($icon_type == 'image') {
      $message_options['icon_url'] = $config->get('slack_icon_url');
    }
    $message_options['as_user'] = TRUE;

    return [
      'webhook_url' => $webhook_url,
      'message_options' => $message_options,
    ];
  }

  /**
   * Send message to the Slack with more options.
   *
   * @param string $webhook_url
   *   Webhook for Slack basic functions.
   * @param string $message
   *   The message sent to the channel.
   * @param array $message_options
   *   An associative array, it can contain:
   *     - channel: The channel in the Slack service to send messages;
   *     - username: The bot name displayed in the channel;
   *     - icon_emoji: The bot icon displayed in the channel;
   *     - icon_url: The bot icon displayed in the channel.
   *
   * @return object
   *   Can contain:
   *                          success    fail         fail
   *     - data:                ok       No hooks     Invalid channel specified
   *     - status message:      OK       Not found    Server Error
   *     - code:                200      404          500
   *     - error:               -        Not found    Server Error
   */
  protected function sendRequest($webhook_url, $message, array $message_options = []) {
    $headers = [
      'Content-Type' => 'application/x-www-form-urlencoded',
    ];
    $message_options['text'] = $this->processMessage($message);
    $sending_data = 'payload=' . urlencode(json_encode($message_options));
    $logger = $this->logger->get('slack');

    try {
      $response = $this->httpClient->request('POST', $webhook_url, ['headers' => $headers, 'body' => $sending_data]);
      $logger->info('Message was successfully sent!');
      return $response;
    }
    catch (ServerException $e) {
      $logger->error('Server error! It may appear if you try to use unexisting chatroom.');
      watchdog_exception('slack', $e);
      return FALSE;
    }
    catch (RequestException $e) {
      $logger->error('Request error! It may appear if you entered the invalid Webhook value.');
      watchdog_exception('slack', $e);
      return FALSE;
    }
    catch (ConnectException $e) {
      $logger->error('Connection error! Something wrong with your connection. Message was\'nt sent.');
      watchdog_exception('slack', $e);
      return FALSE;
    }
  }

  /**
   * Replaces links with slack friendly tags. Strips all other html.
   *
   * @param string $message
   *   The message sent to the channel.
   *
   * @return string
   *   Replaces links with slack friendly tags. Strips all other html.
   */
  protected function processMessage($message) {
    $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";

    if (preg_match_all("/$regexp/siU", $message, $matches, PREG_SET_ORDER)) {
      $i = 1;
      $links = [];
      foreach ($matches as $match) {
        $new_link = "<$match[2] | $match[3]>";
        $links['link-' . $i] = $new_link;
        $message = str_replace($match[0], 'link-' . $i, $message);
        $i++;
      }
      $message = strip_tags($message);
      foreach ($links as $id => $link) {
        $message = str_replace($id, $link, $message);
      }
    }
    return $message;
  }

}
