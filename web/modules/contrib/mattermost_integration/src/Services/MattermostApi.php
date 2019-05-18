<?php

namespace Drupal\mattermost_integration\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides Mattermost API calls as a service.
 *
 * @TODO: Create interface for this class.
 *
 * @package Drupal\mattermost_integration\Services
 */
class MattermostApi {

  /**
   * The config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Mattermost authentication token.
   *
   * @var MattermostApiAccessTokenInterface
   */
  protected $accessToken;

  /**
   * MattermostApi constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param MattermostApiAccessTokenInterface $access_token
   *   The Mattermost authentication token.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MattermostApiAccessTokenInterface $access_token) {
    $this->configFactory = $config_factory;
    $this->accessToken = $access_token;
  }

  /**
   * Function for retrieving information about a post.
   *
   * @param string $post_id
   *   The post ID of the post you want to retrieve.
   * @param string $team_id
   *   The ID of the team in which the message was posted.
   * @param string $channel_id
   *   The ID of the channel in which the message was posted.
   *
   * @return array
   *   An array with the full posts' value.
   */
  public function mattermostApiGetPost($post_id, $team_id, $channel_id) {
    // Get the configuration.
    $config = $this->configFactory->get('mattermost_integration.settings');
    $api_url = $config->get('api_url');
    $api_url = !empty($api_url) ? $api_url : FALSE;
    // Get the authentication token from the state.
    $authentication_token = $this->accessToken->getAccessToken();
    $request_url = $api_url . '/api/v3/teams/' . $team_id . '/channels/' . $channel_id . '/posts/' . $post_id . '/get';

    $curl_handler = curl_init();
    curl_setopt($curl_handler, CURLOPT_URL, $request_url);
    curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_handler, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $authentication_token]);
    $response = curl_exec($curl_handler);
    curl_close($curl_handler);

    return Json::decode($response);
  }

  /**
   * Download a file to local filesystem from the Mattermost server.
   *
   * @param int $file_id
   *   The ID of the file to get.
   *
   * @return string
   *   The file name of the created file.
   */
  public function mattermostApiGetFile($file_id) {
    // Get the configuration.
    $config = $this->configFactory->get('mattermost_integration.settings');
    $api_url = $config->get('api_url');
    $api_url = !empty($api_url) ? $api_url : FALSE;
    // Get the authentication token from the state.
    $authentication_token = $this->accessToken->getAccessToken();
    $request_url = $api_url . '/api/v3/files/' . $file_id . '/get';

    $temporary_file_directory = file_directory_temp();
    $temporary_file = $temporary_file_directory . '/mattermost_integration_file.tmp';
    $file_handle = fopen($temporary_file, 'w+');

    $curl_handler = curl_init();
    curl_setopt($curl_handler, CURLOPT_URL, $request_url);
    curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_handler, CURLOPT_FILE, $file_handle);
    curl_setopt($curl_handler, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $authentication_token]);
    curl_exec($curl_handler);
    curl_close($curl_handler);
    fclose($file_handle);

    $metadata = Json::decode($this->mattermostApiGetFileMetadata($file_id));
    $new_file_name = $metadata['name'];
    rename($temporary_file, $temporary_file_directory . '/' . $metadata['name']);

    return $new_file_name;
  }

  /**
   * Method for getting the metadata associated to a Mattermost file.
   *
   * @param int $file_id
   *   The ID of which metadata to get.
   *
   * @return mixed
   *   The cURL response.
   */
  public function mattermostApiGetFileMetadata($file_id) {
    // Get the configuration.
    $config = $this->configFactory->get('mattermost_integration.settings');
    $api_url = $config->get('api_url');
    $api_url = !empty($api_url) ? $api_url : FALSE;
    // Get the authentication token from the state.
    $authentication_token = $this->accessToken->getAccessToken();
    $request_url = $api_url . '/api/v3/files/' . $file_id . '/get_info';

    $curl_handler = curl_init();
    curl_setopt($curl_handler, CURLOPT_URL, $request_url);
    curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_handler, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $authentication_token]);
    $response = curl_exec($curl_handler);
    curl_close($curl_handler);

    return $response;
  }

}
