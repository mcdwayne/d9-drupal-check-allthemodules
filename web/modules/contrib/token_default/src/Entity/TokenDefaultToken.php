<?php

namespace Drupal\token_default\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Default token entity.
 *
 * @ConfigEntityType(
 *   id = "token_default_token",
 *   label = @Translation("Default token"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\token_default\TokenDefaultTokenListBuilder",
 *     "form" = {
 *       "add" = "Drupal\token_default\Form\TokenDefaultTokenForm",
 *       "edit" = "Drupal\token_default\Form\TokenDefaultTokenForm",
 *       "delete" = "Drupal\token_default\Form\TokenDefaultTokenDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\token_default\TokenDefaultTokenHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "token_default_token",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/token_default_token/{token_default_token}",
 *     "add-form" = "/admin/config/search/token_default_token/add",
 *     "edit-form" = "/admin/config/search/token_default_token/{token_default_token}/edit",
 *     "delete-form" = "/admin/config/search/token_default_token/{token_default_token}/delete",
 *     "collection" = "/admin/config/search/token_default_token"
 *   }
 * )
 */
class TokenDefaultToken extends ConfigEntityBase implements TokenDefaultTokenInterface {

  /**
   * The Default token ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Default token label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Default token pattern.
   *
   * @var string
   */
  protected $pattern;

  /**
   * The Default token string replacement.
   *
   * @var string
   */
  protected $replacement;

  /**
   * The Default token bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  public function getPattern() {
    return $this->pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPattern($pattern) {
    $this->pattern = $pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacement() {
    return $this->replacement;
  }

  /**
   * {@inheritdoc}
   */
  public function setReplacement($replacement) {
    $this->replacement = $replacement;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }

}
