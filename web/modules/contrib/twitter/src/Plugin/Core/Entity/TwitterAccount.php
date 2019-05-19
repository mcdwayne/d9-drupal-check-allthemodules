<?php

namespace Drupal\twitter\Plugin\Core\Entity;

use Drupal\Core\Entity\EntityNG;

/**
 * Defines the twitter account entity class.
 *
 * @EntityType(
 *   id = "twitter_account",
 *   label = @Translation("Twitter account"),
 *   module = "twitter",
 *   controllers = {
 *     "storage" = "Drupal\twitter\TwitterAccountStorageController",
 *     "access" = "Drupal\twitter\TwitterAccountAccessController",
 *     "render" = "Drupal\Core\Entity\EntityRenderController",
 *     "form" = {
 *       "default" = "Drupal\twitter\TwitterAccountFormController",
 *     },
 *     "translation" = "Drupal\twitter\TwitterAccountTranslationController"
 *   },
 *   base_table = "twitter_account",
 *   uri_callback = "twitter_account_uri",
 *   label_callback = "twitter_account_label",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "twitter_uid",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class TwitterAccount extends EntityNG {

  /**
   * Constructor.
   */
  public function __construct($values = []) {
    // Prepare values to match twitter_account table fields.
    if (!empty($values['id'])) {
      $values['twitter_uid'] = $values['id'];
      unset($values['id']);
    }
    if (!empty($values['created_at']) && $created_time = strtotime($values['created_at'])) {
      $values['created_time'] = $created_time;
    }

    $values['utc_offset'] = isset($values['utc_offset']) ? $values['utc_offset'] : 0;
    if (isset($values['status'])) {
      $this->status = new TwitterStatus($values['status']);
      unset($values['status']);
    }
    parent::__construct($values, 'twitter_account');
  }

  /**
   * Returns an array with the authentication tokens.
   *
   * @return array
   *   Public function getAuth array with the oauth token key and secret.
   */
  public function getAuth() {
    return ['oauth_token' => $this->oauth_token, 'oauth_token_secret' => $this->oauth_token_secret];
  }

  /**
   * Sets the authentication tokens to a user.
   *
   * @param array $values
   *   Array with 'oauth_token' and 'oauth_token_secret' keys.
   */
  public function setAuth(array $values) {
    $this->oauth_token = isset($values['oauth_token']) ? $values['oauth_token'] : NULL;
    $this->oauth_token_secret = isset($values['oauth_token_secret']) ? $values['oauth_token_secret'] : NULL;
  }

  /**
   * Checks whether the account is authenticated or not.
   *
   * @return bool
   *   boolean TRUE when the account is authenticated.
   */
  public function isAuth() {
    return !empty($this->oauth_token) && !empty($this->oauth_token_secret);
  }

}
