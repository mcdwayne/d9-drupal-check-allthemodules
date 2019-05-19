<?php

namespace Drupal\social_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_connect\ProcessUser;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\file\Entity\File;

/**
 * Contains the callback handler used by the Social Connect Module.
 */
class SocialConnectHandler extends ControllerBase {

  /**
   * A config object for the social_connect configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Global settings for Social Connect.
   *
   * @var globalsettings
   */
  protected $globalSettings;

  /**
   * Source Facebook/Google.
   *
   * @var source
   */
  protected $source;

  /**
   * All connection settings.
   *
   * @var connections
   */
  protected $connections;

  /**
   * The Connection setting.
   *
   * @var connectionSetting
   */
  protected $connectionSetting;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The process user object.
   *
   * @var \Drupal\social_connect\ProcessUser
   */
  protected $processUser;

  /**
   * Constructs a \Drupal\social_connect\SocialConnectHandler object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The $config.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The $field_manager.
   * @param \Drupal\social_connect\ProcessUser $process_user
   *   The $process_user.
   */
  public function __construct(ConfigFactoryInterface $config, EntityFieldManagerInterface $field_manager, ProcessUser $process_user) {
    $this->config = $config->get('social_connect.settings');
    $this->connections = $this->config->get('connections');
    $this->globalSettings = $this->config->get('global');
    $this->fieldManager = $field_manager;
    $this->processUser = $process_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config = $container->get('config.factory');
    $entity_field_manager = $container->get('entity_field.manager');
    $process_user = $container->get('social_connect.manager');
    return new static($config, $entity_field_manager, $process_user);
  }

  /**
   * Validates if request is valid or not.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   Returns TRUE/FALSE.
   */
  private function isValidRequest(Request $request) {
    // Try to get current config.
    if (empty($this->config)) {
      $response = [
        'message' => $this->t('Config is not set.'),
      ];
      return new JsonResponse($response, 500);
    }

    // Validate connection.
    if (!in_array($request->get('source'), ['facebook', 'google'])) {
      $response = [
        'message' => $this->t('Field type can either be facebook/google.'),
      ];
      return new JsonResponse($response, 500);
    }

    // Check if access token exists.
    if (empty($request->get('access_token'))) {
      $response = [
        'message' => $this->t('Field access_token is required.'),
      ];
      return new JsonResponse($response, 500);
    }
    return TRUE;
  }

  /**
   * Helper function to handle ajax call request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns json output.
   */
  public function handle(Request $request) {
    $isValidRequest = $this->isValidRequest($request);
    if ($isValidRequest !== TRUE) {
      return $isValidRequest;
    }

    // Get and set source.
    $this->source = $request->get('source');

    // Set connection config based on source.
    $this->connectionSetting = $this->connections[$this->source];

    // Get access token.
    $access_token = $request->get('access_token');

    return $this->socialLogin($access_token);
  }

  /**
   * Get access token as input and returns api based on social call.
   *
   * @param string $access_token
   *   The access token.
   *
   * @return url
   *   Retuns API url.
   */
  private function getApi($access_token) {
    $base_url = '';

    $query = [
      'access_token' => $access_token
    ];

    switch ($this->source) {
      case "facebook":
        $base_url = 'https://graph.facebook.com/me';
        $query['fields'] = implode(',', [
          'id', 'age_range', 'email', 'first_name', 'gender', 'last_name', 'link', 'locale', 'middle_name', 'name', 'timezone', 'verified'
        ]);
        break;
      case "google":
        $base_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        break;
    }

    return Url::fromUri($base_url, ['query' => $query])->toString();
  }

  /**
   * Process login.
   *
   * @param string $access_token
   *   The access token.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns Json output
   */
  private function socialLogin($access_token) {
    $api = $this->getApi($access_token);
    $user_info = [];
    try {
      $client = new Client();
      $response = $client->get($api);
      $result = $response->getBody()->getContents();
      $user_info = Json::decode($result);
    }
    catch (RequestException $ex) {
      \Drupal::logger('social_connect')->error('Faild to get user info from @source.', ['@source' => $this->source]);
    }
    if (isset($user_info['error'])) {
      $response = [
        'message' => $this->t('Access token provided is invalid or something went wrong while fetching data from @source.', ['@source' => $this->source]),
      ];
      return new JsonResponse($response, 500);
    }

    // Clear userinfo from not valid keys.
    foreach ($user_info as $key => $value) {
      if (empty($value)) {
        unset($user_info[$key]);
      }
      // Set id for google.
      if ($key == 'sub') {
        $user_info['id'] = $value;
        unset($user_info['sub']);
      }
      // Set gender value as M/F.
      if ($key == 'gender') {
        $user_info['gender'] = ($value == 'female') ? 'F' : 'M';
      }
      if ($key == 'age_range') {
        // Set min age.
        if (isset($value['min'])) {
          $user_info['age_min'] = $value['min'];
        }
        // Set max age.
        if (isset($value['min'])) {
          $user_info['age_max'] = $value['max'];
        }
        unset($user_info['age_range']);
      }
    }

    if (!isset($user_info['email'])) {
      $response = [
        'message' => $this->t('Can\'t login with @source account (no email presented in response.)', ['@source' => $this->source]),
      ];
      return new JsonResponse($response, 403);
    }

    if (isset($user_info['picture']) && !empty($user_info['picture'])) {
      $user_info['profilepicture'] = $user_info['picture'];
    }

    $picture_size = $this->connectionSetting['picture_size'];
    // Adding size to profile picture
    if ($this->source == 'facebook') {
      $user_info['profilepicture'] = $this->getFbImageUrl($user_info['id'], $picture_size);
    }
    else {
      $sz = 0;
      switch ($picture_size) {
        case "small":
          $sz = 50;
          break;
        case "normal":
          $sz = 100;
          break;
        case "large":
          $sz = 200;
          break;
      }
      if ($sz > 0) {
        $user_info['profilepicture'] .= '?sz=' . $sz;
      }
    }

    $username = $user_info['email'];

    // Before creating new user we need to check if username already exists.
    $account = user_load_by_mail($username);
    if (!$account) {
      $account = $this->processUser->createUser($username, $username);
      if (!$account) {
        $response = [
          'message' => $this->t('Error creating user account.'),
        ];
        return new JsonResponse($response, 403);
      }
    }

    $this->updateUser($user_info, $account);

    $this->processUser->externalAuthLoginRegister($this->source, $account);

    $response = [
      'message' => $this->t('Logged in.'),
    ];
    return new JsonResponse($response);
  }

  /**
   * Prepares user data and update.
   *
   * @param array $user_info
   *   The user info array.
   * @param object $account
   *   The account object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns json output.
   */
  private function updateUser(array $user_info, $account) {
    $fieldMaps = $this->connectionSetting['field_maps'];
    $edit = [];
    $picture_profile_field = NULL;
    foreach ($fieldMaps as $fieldMap) {
      if ($fieldMap['source_field'] == 'picture') {
        $picture_profile_field = $fieldMap['profile_field'];
      }
      else {
        if ($fieldMap['override'] || $account->{$fieldMap['profile_field']}->isEmpty()) {
          $source_field = $fieldMap['source_field'];
          $profile_field = $fieldMap['profile_field'];
          $value = $user_info[$fieldMap['source_field']];
          $edit[$profile_field] = $value;
        }
      }
    }

    if ($picture_profile_field && isset($user_info['profilepicture'])) {

      if ($this->connectionSetting['picture_override'] || !isset($account->{$picture_profile_field}) || $account->{$picture_profile_field}->isEmpty()) {
        // delete pre saved image
        if (!$account->{$picture_profile_field}->isEmpty()) {
          $previous_fid = $account->get($picture_profile_field)->target_id;
          file_delete($previous_fid);
        }
        // download and save new image
        $fid = $this->savePicture($user_info, $picture_profile_field);
        if ($fid) {
          $edit[$picture_profile_field] = ['target_id' => $fid];
        }
      }
    }

    if (isset($user_info['timezone'])) {
      $offset = $user_info['timezone'] * 3600;
      // Gets daylight savings.
      $dst = date("I");
      $timezone = timezone_name_from_abbr("", $offset, $dst);
      $edit['timezone'] = $timezone;
    }

    if (!empty($edit)) {
      $update = $this->processUser->updateUser($account, $edit);
      if ($update === FALSE) {
        $response = [
          'message' => $this->t('Error saving user account.'),
        ];
        return new JsonResponse($response, 403);
      }
    }
  }

  /**
   * 
   * @param type $id
   * @return facebook profile image url
   */
  private function getFBImageUrl($id, $size) {
    $url = "http://graph.facebook.com/$id/picture?type=$size&redirect=false";
    try {
      $client = new Client();
      $res = $client->get($url, ['http_errors' => false])->getBody()->getContents();
      $social_info = (object) Json::decode($res);
      return $social_info->data['url'];
    }
    catch (RequestException $e) {
      $jsonResponse = [
        'status' => [
          'code' => 422,
          'message' => 'Error while fetching image from facebook.',
        ]
      ];
      return new JsonResponse($jsonResponse, 422);
    }
  }

  /**
   * Download image and save.
   *
   * @param array $user_info
   *   The user info array.
   *
   * @return int
   *   The file id of saved image.
   */
  private function savePicture($user_info, $picture_profile_field) {
    $profile_fields = $this->fieldManager->getFieldDefinitions('user', 'user');
    $picture_field = $profile_fields[$picture_profile_field];
    $uri_scheme = $picture_field->getSetting('uri_scheme');
    $file_directory = $picture_field->getSetting('file_directory');
    $uri = $uri_scheme . '://' . $file_directory;
    $destination_dir = \Drupal::token()->replace($uri);
    file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $picture = file_get_contents($user_info['profilepicture']);
    if ($file = file_save_data($picture, $destination_dir . '/' . $user_info['id'] . '-' . REQUEST_TIME . '.jpeg', FILE_EXISTS_REPLACE)) {
      return $file->id();
    }
    return NULL;
  }

}
