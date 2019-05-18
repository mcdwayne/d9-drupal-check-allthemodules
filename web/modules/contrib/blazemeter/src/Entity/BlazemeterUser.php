<?php

/**
 * @file
 * Contains \Drupal\blazemeter\Entity\BlazemeterUser.
 */

namespace Drupal\blazemeter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\blazemeter\BlazemeterUserInterface;

/**
 * Defines the Blazemeter user entity.
 *
 * @ConfigEntityType(
 *   id = "blazemeter_user",
 *   label = @Translation("Blazemeter user"),
 *   handlers = {
 *     "list_builder" = "Drupal\blazemeter\BlazemeterUserListBuilder",
 *     "form" = {
 *       "add" = "Drupal\blazemeter\Form\BlazemeterUserForm",
 *       "edit" = "Drupal\blazemeter\Form\BlazemeterUserForm",
 *       "delete" = "Drupal\blazemeter\Form\BlazemeterUserDeleteForm"
 *     }
 *   },
 *   config_prefix = "blazemeter_user",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "username" = "username",
 *     "password" = "password"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/blazemeter_user/{blazemeter_user}",
 *     "edit-form" = "/admin/structure/blazemeter_user/{blazemeter_user}/edit",
 *     "delete-form" = "/admin/structure/blazemeter_user/{blazemeter_user}/delete",
 *     "collection" = "/admin/structure/visibility_group"
 *   }
 * )
 */
class BlazemeterUser extends ConfigEntityBase implements BlazemeterUserInterface {
  /**
   * The Blazemeter user ID.
   *
   * @var string
   */
  /**
   * The Blazemeter user label.
   *
   * @var string
   */
  protected $username;
  protected $password;

  public function username() {
    // TODO: Implement username() method.
    return $this->username;
  }

  public function password() {
    // TODO: Implement password() method.
    return $this->password;
  }
}
