<?php

namespace Drupal\services_api_key_auth\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\services_api_key_auth\ApiKeyInterface;

/**
 * Defines the Api key entity.
 *
 * @ConfigEntityType(
 *   id = "api_key",
 *   label = @Translation("Api key"),
 *   handlers = {
 *     "list_builder" = "Drupal\services_api_key_auth\Controller\ApiKeyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\services_api_key_auth\Form\ApiKeyForm",
 *       "edit" = "Drupal\services_api_key_auth\Form\ApiKeyForm",
 *       "delete" = "Drupal\services_api_key_auth\Form\ApiKeyDeleteForm"
 *     },
 *   },
 *   config_prefix = "api_key",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "key" = "key"
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/api_key",
 *     "edit-form" = "/admin/config/services/api_key/{api_key}/edit",
 *     "delete-form" = "/admin/config/services/api_key/{api_key}/delete",
 *   }
 * )
 */
class ApiKey extends ConfigEntityBase implements ApiKeyInterface {
  /**
   * The Api key ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Api key label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Api key.
   *
   * @var string
   */
  public $key;

  /**
   * The User UUID.
   *
   * @var string
   */
  public $user_uuid;

}
