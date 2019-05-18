<?php

namespace Drupal\openid_connect_rest\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\openid_connect_rest\StateTokenInterface;

/**
 * Defines the state token entity class.
 *
 * @package Drupal\openid_connect_rest\Entity
 *
 * @ingroup openid_connect_rest
 *
 * @ConfigEntityType(
 *   id = "state_token",
 *   label = @Translation("OpenID Connect REST state token"),
 *   admin_permission = "administer site configuration",
 *   handlers = {
 *     "list_builder" = "Drupal\openid_connect_rest\Controller\StateTokenController",
 *     "form" = {
 *       "delete" = "Drupal\openid_connect_rest\Form\StateToken\DeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/services/openid-connect-rest/state-tokens/{state_token}/delete"
 *   }
 * )
 */
class StateToken extends ConfigEntityBase implements StateTokenInterface {

  /**
   * The state token id.
   *
   * @var string
   */
  public $id;

  /**
   * The state token.
   *
   * @var string
   */
  public $state_token;

  /**
   * The expiration date.
   *
   * @var string
   */
  public $expires;

}
