<?php

namespace Drupal\edstep;

use Edstep\Client;
use Drupal\Core\Url;
use League\OAuth2\Client\Token\AccessToken;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Edstep\Exception\RefreshAccessTokenException;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use \Drupal\user\Entity\User;

class EdstepService {

  protected $client;

  public function __construct() {
  }

  public function getClient() {
    if(!isset($this->client)) {
      $url = Url::fromRoute('edstep.oauth_return_uri')
        ->setAbsolute(TRUE)
        ->toString(TRUE)
        ->getGeneratedUrl();

      $this->client = new Client(
        $this->getSetting() + [
          'auth_redirect_uri' => $url,
        ]
      );
      $this->client->setAccessToken($this->loadAccessToken());
    }
    return $this->client;
  }

  public function storeAccessToken($token) {
    \Drupal::database()->merge('edstep_access_token')
      ->key([
        'uid' => \Drupal::currentUser()->id()
      ])
      ->fields([
        'uid' => \Drupal::currentUser()->id(),
        'access_token' => $token->getToken(),
        'refresh_token' => $token->getRefreshToken(),
        'expires' => $token->getExpires(),
      ])
      ->execute();

    return $this;
  }

  public function loadAccessToken() {
    $result = \Drupal::database()->select('edstep_access_token', 'eat')
      ->fields('eat', ['uid', 'access_token', 'refresh_token', 'expires'])
      ->condition('eat.uid', \Drupal::currentUser()->id())
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();

    if(!$result) {
      return NULL;
    }
    $token = new AccessToken($result);

    return $token;
  }

  public function authorize($destination = NULL) {
    $destination = $destination ? $destination : 'internal:' . \Drupal::request()->getRequestUri();
    try {
      $access_token = $this->getClient()->refreshAccessToken()->getAccessToken();
      $this->storeAccessToken($access_token);

    } catch(RefreshAccessTokenException $e) {
      $client = \Drupal::service('edstep.edstep')->getClient();
      $url = Url::fromUri($client->getProvider()->getAuthorizationUrl(['destination' => $destination]));

      $tempstore = \Drupal::service('user.private_tempstore')->get('edstep');
      $tempstore->set('auth_state', $client->getProvider()->getState());

      return new TrustedRedirectResponse($url->toString());
    }
  }

  public function getFieldMappings($entity_type) {
    return $this->getSetting("field_mappings.$entity_type") ?: [];
  }

  public function getFieldMapping($entity_type, $field_name) {
    return $this->getSetting("field_mappings.$entity_type.$field_name") ?: [];
  }

  public function getSetting($key = '') {
    return \Drupal::config('edstep.settings')->get($key);
  }

  public function getHost() {
    return $this->getSetting('host');
  }

  public function getActivityResourceUrl($course, $section_id, $activity_id) {
    $host = $this->getHost();
    return Url::fromUri("$host/$course->public_id/$section_id/$activity_id");
  }

  public function syncCurrentUserData() {
    $entity = User::load(\Drupal::currentUser()->id());
    $remote = $this->getClient()->getCurrentUser();
    $this->syncData($entity, $remote);
    $entity->save();
  }

  protected function syncData(ContentEntityInterface $entity, $remote) {
    $field_mappings = $this->getFieldMappings($entity->getEntityTypeId());
    foreach($field_mappings as $field_name => $settings) {
      // Don’t do anything if the entity doesn’t have the field
      if(!$entity->hasField($field_name)) {
        continue;
      }
      // Fetch the remote values for the source prop
      $prop = $settings['source'];
      $values = $remote->$prop;
      // If the value is an object we assume it’s iterable. Cast to array if it’s not an object
      if(!is_object($values)) {
        $values = (array) $values;
      }
      // Empty the field
      $entity->$field_name->setValue([]);
      // Then loop through the values
      foreach($values as $value) {
        // Skip the value if it’s NULL or an empty string
        if(!isset($value) || $value === '') {
          continue;
        }
        // If the value is an object we assume it has an `id` property and use that
        if(is_object($value)) {
          $value = $value->id;
        }
        // Append the value to the field item list
        $entity->$field_name->appendItem($value);
      }
    }
  }
}
