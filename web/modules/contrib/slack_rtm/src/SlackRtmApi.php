<?php

namespace Drupal\slack_rtm;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Drupal\Core\Url;

/**
 * Wrapper class for the Slack RTM API.
 *
 * @ingroup slack_rtm
 */
class SlackRtmApi {

  /**
   * The base URL for API requests.
   */
  const BASE_URL = 'https://slack.com/api/';

  /**
   * The url we are checking.
   *
   * @var string
   */
  protected $url;

  /**
   * The Request Response.
   *
   * @var object
   */
  protected $request;

  /**
   * The channels array.
   *
   * @var array
   */
  protected $channels;

  /**
   * The users array
   *
   * @var array
   */
  protected $users = [];

  /**
   * Set the url we are checking.
   *
   * @param string $url
   *   The url string we are checking.
   */
  protected function setUrl($url) {
    $this->url = $url;
  }

  /**
   * Get the url we are checking.
   *
   * @return string
   *   The url string we are checking.
   */
  protected function getUrl() {
    return $this->url;
  }

  /**
   * Get the url we are checking.
   *
   * @param string $type
   *   The type of token, choices right now are.
   *   slack_bot_token / slack_user_token
   *
   * @return string
   *   The type of token we are getting.
   */
  public function getToken($type) {
    $token = \Drupal::config('slack_rtm.settings')->get($type);
    return $token;
  }

  /**
   * Mechanism to call the API.
   *
   * @param string $endpoint
   *   The endpoint url without the const BASE_URL.
   * @param array $params
   *   Additional paramaters to send in the url.
   * @param string $type
   *   The type of request (ie GET, POST, etc).
   *
   * @return null|object
   *   The API response or NULL.
   */
  public function callApi($endpoint, array $params = [], $type = 'GET') {

    // Format them so we can process the url.
    $options = [
      'query' => $params,
    ];

    // Build the url and call the API.
    $url = Url::fromUri(self::BASE_URL . $endpoint, $options)->toString();
    $this->setUrl($url);
    $this->requestResponse($type);

    // Exit if Empty Response.
    if (is_null($this->request)) {
      return NULL;
    }

    // Grab the Body and return as needed.
    $contents = json_decode($this->request->getBody()->getContents(), TRUE);
    return !empty($contents) ? $contents : NULL;
  }

  /**
   * Gets the response of a page.
   *
   * @param string $type
   *   The type of request (ie GET, POST, etc).
   */
  protected function requestResponse($type) {
    $client = new Client();

    // Set the options for the request.
    // @see http://docs.guzzlephp.org/en/latest/request-options.html
    $options = [
      'http_errors' => FALSE,
      'timeout' => 3,
      'connect_timeout' => 3,
      'synchronous' => TRUE,
    ];

    try {
      // Try the request.
      $response = $client->request($type, $this->getUrl(), $options);

      // Check the Status code and return.
      switch ($response->getStatusCode()) {
        // All good, send back response.
        case '200':
          $this->request = $response;
          break;

        // Something else is amiss.
        default:
          $message = 'The request to the Slack API resulted in a ' . $response->getStatusCode() . ' Response. ';
          \Drupal::logger('OneHub API')->error($message);
          $this->request = NULL;
          break;
      }
    }
    catch (TransferException $e) {
      $this->request = NULL;
    }
  }

 /**
 * Grabs all the messages from channels selected in config.
 *
 * @param bool $isBatch
 *   Are we setting a UI based batch or not.
 *
 * @return array
 */
  public function getMessages($isBatch = TRUE) {
    // Grab all the channels to loop through from config.
    // @todo make this all injectable.
    $config = \Drupal::config('slack_rtm.settings');
    $channels_selected = $config->get('slack_channels');
    $channels_list = $config->get('slack_channels_list');
    $params['token'] = $this->getToken('slack_bot_token');
    $db = \Drupal::database();

    // Grab the users.
    $this->getUsers();

    // Grab all the selected channels to process.
    foreach ($channels_selected as $id => $name) {
      // If the channel is not selected, then get out.
      if ($name == '0') {
        continue;
      }

      $params = [
        'channel' => $id,
        'count' => 1000,
        'token' => $this->getToken('slack_bot_token'),
      ];

      // Now we can grab the public channel messages.
      $messages = $this->callApi('channels.history', $params);

      // If error, then it is a private channel.
      if (isset($messages['error']) && $messages['error']) {
        $private = $config->get('slack_include_private');

        if ($private) {
          $params['token'] = $this->getToken('slack_user_token');
          $messages = $this->callApi('groups.history', $params);
        }
      }

      foreach ($messages['messages'] as $message) {
        if ($message['type'] == 'message') {

          $result = $db->select('slack_rtm_message_field_data', 's')
            ->fields('s', ['tid'])
            ->condition('tid', $message['ts'])
            ->execute()
            ->fetchAll();

          // If the message exists, then continue.
          // @todo check if the message has been updated.
          if (!empty($result)) {
            continue;
          }

          // Easier to pass this all as one.
          $batch = [
            'message' => $message,
            'users' => $this->users,
            'id' => $id,
            'channels_list' => $channels_list,
          ];

          // Setup the right batch operations.
          if ($isBatch) {
            $operations[] = ['Drupal\slack_rtm\Batch\SlackRtmBatch::batchProcess', [$batch]];
          }
          elseif (!$isBatch) {
            $queues[] = $batch;
          }

        }
      }
    }


    // Process DMs separate since the are rendered a little different.
    $dms = $this->getDirectMessages();
    foreach ($dms as $dm) {
      // Setup the right batch operations.
      if ($isBatch) {
        $operations[] = ['Drupal\slack_rtm\Batch\SlackRtmBatch::batchProcess', [$dm]];
      }
      elseif (!$isBatch) {
        $queues[] = $dm;
      }
    }

    // This is for the queue based batch,
    if (isset($queues) && !$isBatch) {
      return $queues;
    }

    // This is for the ui based batch.
    if (isset($operations) && $isBatch) {
      // Set the batch to win the stuff.
      $batch = array(
        'title' => t('Assimilating Slack Messages...'),
        'operations' => $operations,
        'init_message' => t('Importing Messages to process.'),
        'finished' => 'Drupal\slack_rtm\Batch\SlackRtmBatch::batchFinished',
        'file' => drupal_get_path('module', 'slack_rtm') . '/src/Batch/SlackRtmBatch.php'
      );

      // Engage.
      batch_set($batch);
    }
    else {
      drupal_set_message(t('No Messages to Process!'), 'warning', TRUE);
    }

    // Fail safe return.
    return [];
  }

  /**
   * Grabs the users from Slack.
   */
  protected function getUsers() {
    // Need to load the user list since we can't call call the user.
    // profile directly in the Slack API.
    $params['token'] = $this->getToken('slack_bot_token');
    $users_list = $this->callApi('users.list', $params);

    foreach ($users_list['members'] as $user) {
      $this->users[$user['id']] = $user['name'];
    }
  }

  /**
   * Grabs the Channels for the registered Slack app.
   *
   * @return array
   *   The Slack channels.
   */
  public function getChannels() {
    // Grab all the channels and separate them into an array.
    $params['token'] = $this->getToken('slack_bot_token');
    $channel_list = $this->callApi('channels.list', $params);
    $channels = [];
    foreach ($channel_list["channels"] as $channel) {
      if ($channel["is_channel"]) {
        $channels[$channel["id"]] = $channel["name"];
      }
    }

    $config = \Drupal::config('slack_rtm.settings');
    $private = $config->get('slack_include_private');

    if ($private) {
      $params['token'] = $this->getToken('slack_user_token');
      $private_list = $this->callApi('groups.list', $params);
      foreach ($private_list["groups"] as $channel) {
        if (!$channel['is_mpim']) {
          $channels[$channel["id"]] = $channel["name"];
        }
      }
    }

    // Sort and return.
    asort($channels);
    return $channels;
  }

  /**
   * Grabs a specified Channel info.
   *
   * @param string $channel
   *   The channel ID that we are looking for.
   *
   * @return object|NULL
   *   The Slack channel info.
   */
  public function getChannelInfo($channel) {
    $params['token'] = $this->getToken('slack_bot_token');
    $params['channel'] = $channel;
    $info = $this->callApi('channels.info', $params);

    // Private channels.
    if (isset($info['ok']) && !$info['ok']) {
      $params['token'] = $this->getToken('slack_user_token');
      $info = $this->callApi('groups.info', $params);
    }

    // Return the correct value.
    if (isset($info['channel'])) {
      return $info['channel'];
    }
    elseif (isset($info['group'])) {
      return $info['group'];
    }
    else {
      return NULL;
    }
  }

  /**
   * Grabs all direct messages.
   *
   * @return array|NULL
   *   The Slack Direct Messages.
   */
  public function getDirectMessages() {

    $config = \Drupal::config('slack_rtm.settings');
    $dm = $config->get('slack_include_dm');

    if ($dm) {
      $params['token'] = $this->getToken('slack_bot_token');
      $params['types'] = 'mpim, im';
      $dm_list = $this->callApi('conversations.list', $params);

      $channels = $batch = [];
      foreach ($dm_list["channels"] as $channel) {
        if ($channel["is_im"]) {
          $channels[$channel["id"]] = $this->users[$channel['user']];
        }
      }

      // Go through the channels and set up the batch,
      if (!empty($channels)) {
        foreach ($channels as $channel => $user) {
          $params['token'] = $this->getToken('slack_bot_token');
          $params['channel'] = $channel;
          $messages = $this->callApi('conversations.history', $params);

          // if there are messages.
          if (isset($messages["messages"]) && !empty($messages["messages"])) {
            foreach ($messages["messages"] as $message) {
              // Parse out the DM user + text.
              $message["text"] = '<@' . $message["user"] . '>' . ' ' . $message["text"];
              $batch[] = [
                'message' => $message,
                'users' => $this->users,
                'id' => $user,
              ];
            }
          }
        }
      }

      // Return the results.
      return !empty($batch) ? $batch : NULL;
    }
    else {
      return NULL;
    }
  }
}