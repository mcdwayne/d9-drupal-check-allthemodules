<?php

namespace Drupal\openid_connect_rest\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\openid_connect_rest\AuthorizationMappingInterface;

/**
 * Defines the authorization mapping entity class.
 *
 * @package Drupal\openid_connect_rest\Entity
 *
 * @ingroup openid_connect_rest
 *
 * @ConfigEntityType(
 *   id = "authorization_mapping",
 *   label = @Translation("OpenID Connect REST authorization mapping"),
 *   admin_permission = "administer site configuration",
 *   handlers = {
 *     "list_builder" = "Drupal\openid_connect_rest\Controller\AuthorizationMappingController",
 *     "form" = {
 *       "delete" = "Drupal\openid_connect_rest\Form\AuthorizationMapping\DeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/services/openid-connect-rest/authorization-mappings/{authorization_mapping}/delete"
 *   }
 * )
 */
class AuthorizationMapping extends ConfigEntityBase implements AuthorizationMappingInterface {

  /**
   * The mapping id.
   *
   * @var string
   */
  public $id;

  /**
   * The authorization code.
   *
   * @var string
   */
  public $authorization_code;

  /**
   * The state token.
   *
   * @var string
   */
  public $state_token;

  /**
   * The sub identifying a user.
   *
   * @var string
   */
  public $user_sub;

  /**
   * The expiration date.
   *
   * @var string
   */
  public $expires;

}
