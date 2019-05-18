<?php

namespace Drupal\pfdp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Private files download permission directory entity.
 *
 * @ConfigEntityType(
 *   id = "pfdp_directory",
 *   label = @Translation("Private files download permission directory"),
 *   module = "pfdp",
 *   config_prefix = "pfdp_directory",
 *   handlers = {
 *     "list_builder" = "Drupal\pfdp\DirectoryListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pfdp\Form\DirectoryForm",
 *       "edit" = "Drupal\pfdp\Form\DirectoryForm",
 *       "delete" = "Drupal\pfdp\Form\DirectoryDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer pfdp",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "edit" = "/admin/config/media/private-files-download-permission/{pfdp_directory}",
 *     "delete" = "/admin/config/media/private-files-download-permission/{pfdp_directory}/delete",
 *   },
 * )
 */
class DirectoryEntity extends ConfigEntityBase implements DirectoryEntityInterface {

  public $id = NULL;
  public $path = NULL;
  public $bypass = FALSE;
  public $grant_file_owners = FALSE;
  public $users = [];
  public $roles = [];

}
