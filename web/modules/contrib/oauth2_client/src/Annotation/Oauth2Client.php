<?php

namespace Drupal\oauth2_client\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation definition Oauth2Client plugins.
 *
 * @Annotation
 */
class Oauth2Client extends Plugin {

  /**
   * The OAuth 2 plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the OAuth2 Client.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The client_id of the OAuth2 server.
   *
   * @var string
   */
  public $client_id;

  /**
   * The grant type of the OAuth2 authorization.
   *
   * Possible values:
   * - authorization_code
   * - client_credentials
   * - resource_owner - Note that the 'username' and 'password' attributes are
   *   required for this type.
   *
   * @var string
   */
  public $grant_type;

  /**
   * The client_secret of the OAuth2 server.
   *
   * @var string
   */
  public $client_secret;

  /**
   * The authorization endpoint of the OAuth2 server.
   *
   * @var string
   */
  public $authorization_uri;

  /**
   * The token endpoint of the OAuth2 server.
   *
   * @var string
   */
  public $token_uri;

  /**
   * The resource endpoint of the OAuth2 Server.
   *
   * @var string
   */
  public $resource_uri;

  /**
   * The Resource Owner Details endpoint.
   *
   * @var string
   */
  public $resource_owner_uri;

  /**
   * The set of scopes for the provider to use by default.
   *
   * @var array|string|null
   */
  public $scopes;

  /**
   * The separator used to join the scopes in the OAuth2 query string.
   *
   * @var string|null
   */
  public $scope_separator;

  /**
   * The username of the account being authenticated.
   *
   * Note: Used only when the grant_type is set to resource_owner.
   *
   * @var string
   */
  public $username;

  /**
   * The password of the account being authenticated.
   *
   * Note: Used only when the grant_type is set to resource_owner.
   *
   * @var string
   */
  public $password;

}
