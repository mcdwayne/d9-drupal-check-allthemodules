<?php

namespace Drupal\shield_pages\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\shield_pages\ShieldPageInterface;

/**
 * Defines the Shield page entity.
 *
 * @ConfigEntityType(
 *   id = "shield_page",
 *   label = @Translation("Shield page"),
 *   handlers = {
 *     "list_builder" = "Drupal\shield_pages\ShieldPageListBuilder",
 *     "form" = {
 *       "default" = "Drupal\shield_pages\Form\ShieldPageEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer shield configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "path",
 *     "passwords",
 *     "weight"
 *   },
 *   links = {
 *     "collection" = "/admin/config/system/shield-pages",
 *     "edit-form" = "/admin/config/system/shield-pages/{shield_page}",
 *     "delete-form" = "/admin/config/system/shield-pages/{shield_page}/delete"
 *   }
 * )
 */
class ShieldPage extends ConfigEntityBase implements ShieldPageInterface {

  /**
   * The Shield page ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Shield page label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Shield page relative path.
   *
   * @var string
   */
  protected $path;

  /**
   * The Shield page passwords.
   *
   * @var array
   */
  protected $passwords = [];

  /**
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPasswords() {
    return $this->passwords;
  }

  /**
   * {@inheritdoc}
   */
  public function setPasswords($passwords) {
    $this->passwords = $passwords;
    return $this;
  }
}
