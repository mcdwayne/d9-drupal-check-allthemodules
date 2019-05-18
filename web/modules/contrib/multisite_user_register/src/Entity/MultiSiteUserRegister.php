<?php

namespace Drupal\multisite_user_register\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Multi Site User Register entity.
 *
 * @ConfigEntityType(
 *   id = "multi_site_user_register",
 *   label = @Translation("Multi Site User Register"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\multisite_user_register\MultiSiteUserRegisterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\multisite_user_register\Form\MultiSiteUserRegisterForm",
 *       "edit" = "Drupal\multisite_user_register\Form\MultiSiteUserRegisterForm",
 *       "delete" = "Drupal\multisite_user_register\Form\MultiSiteUserRegisterDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\multisite_user_register\MultiSiteUserRegisterHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "multi_site_user_register",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "username" = "username",
 *     "password" = "password",
 *     "url" = "url"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/multi_site_user_register/{multi_site_user_register}",
 *     "add-form" = "/admin/structure/multi_site_user_register/add",
 *     "edit-form" = "/admin/structure/multi_site_user_register/{multi_site_user_register}/edit",
 *     "delete-form" = "/admin/structure/multi_site_user_register/{multi_site_user_register}/delete",
 *     "collection" = "/admin/structure/multi_site_user_register"
 *   }
 * )
 */
class MultiSiteUserRegister extends ConfigEntityBase implements MultiSiteUserRegisterInterface {

  /**
   * The Multi Site User Register ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Multi Site User Register label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Multi User Register site url.
   *
   * @var string
   */
  protected $url;

  /**
   * The Multi User Register site username.
   *
   * @var string
   */
  protected $username;

  /**
   * The Multi User Register site password.
   *
   * @var string
   */
  protected $password;

  /**
   * {@inheritdoc}
   */
  public function get_username() {
    return $this->username;
  }

  /**
   * {@inheritdoc}
   */
  public function get_password() {
    return $this->password;
  }

  /**
   * {@inheritdoc}
   */
  public function get_url() {
    return $this->url;
  }

}