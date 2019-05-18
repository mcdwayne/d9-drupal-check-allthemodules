<?php

namespace Drupal\custom_tokens\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines a token entity for the creation of custom tokens.
 *
 * @ConfigEntityType(
 *   id = "token",
 *   label = @Translation("Token"),
 *   label_collection = @Translation("Tokens"),
 *   handlers = {
 *     "list_builder" = "Drupal\custom_tokens\Entity\TokenListBuilder",
 *     "form" = {
 *       "add" = "Drupal\custom_tokens\Form\TokenEntityForm",
 *       "edit" = "Drupal\custom_tokens\Form\TokenEntityForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "token",
 *   admin_permission = "administer custom tokens",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/custom-tokens/add",
 *     "edit-form" = "/admin/structure/custom-tokens/{token}/edit",
 *     "delete-form" = "/admin/structure/custom-tokens/{token}/delete",
 *     "collection" = "/admin/structure/custom-tokens"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "tokenValue",
 *     "tokenName"
 *   }
 * )
 */
class TokenEntity extends ConfigEntityBase implements TokenEntityInterface {

  /**
   * The ID of the entity and the name of the token.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  protected $tokenValue;

  /**
   * The token name.
   *
   * @var string
   */
  protected $tokenName;

  /**
   * The entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function getTokenName() {
    return $this->tokenName;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenValue() {
    return $this->tokenValue;
  }

}
