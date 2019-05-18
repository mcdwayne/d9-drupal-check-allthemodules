<?php

/**
 * @file
 * Contains \Drupal\elfinder\Entity\elFinderProfile.
 */

namespace Drupal\elfinder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Profile entity
 *
 * @ConfigEntityType(
 *   id = "elfinder_profile",
 *   label = @Translation("elFinder Profile"),
 *   handlers = {
 *     "list_builder" = "Drupal\elfinder\Controller\elFinderProfileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\elfinder\Form\elFinderProfileForm",
 *       "edit" = "Drupal\elfinder\Form\elFinderProfileForm",
 *       "delete" = "Drupal\elfinder\Form\elFinderProfileDeleteForm",
 *       "duplicate" = "Drupal\elfinder\Form\elFinderProfileForm"
 *     }
 *   },
 *   admin_permission = "administer elfinder",
 *   config_prefix = "profile",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/media/elfinder/profile/{elfinder_profile}/edit",
 *     "delete-form" = "/admin/config/media/elfinder/profile/{elfinder_profile}/delete",
 *     "duplicate-form" = "/admin/config/media/elfinder/profile/{elfinder_profile}/duplicate"
 *   }
 * )
 */
class elFinderProfile extends ConfigEntityBase {

  /**
   * Profile ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Label.
   *
   * @var string
   */
  protected $label;

  /**
   * Description.
   *
   * @var string
   */
  protected $description;

  /**
   * Configuration options.
   *
   * @var array
   */
  protected $conf = array();

  /**
   * Returns configuration options.
   */
  public function getConf($key = NULL, $default = NULL) {
    $conf = $this->conf;
    if (isset($key)) {
      return isset($conf[$key]) ? $conf[$key] : $default;
    }
    return $conf;
  }

}
