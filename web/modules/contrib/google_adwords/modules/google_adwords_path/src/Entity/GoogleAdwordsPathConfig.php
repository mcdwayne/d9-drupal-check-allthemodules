<?php

/**
 * @file
 * Contains Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig.
 */

namespace Drupal\google_adwords_path\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the Google AdWords Path Config entity.
 *
 * @ConfigEntityType(
 *   id = "google_adwords_path_config",
 *   label = @Translation("Google AdWords Path Config"),
 *   handlers = {
 *     "list_builder" = "Drupal\google_adwords_path\GoogleAdwordsPathConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\google_adwords_path\Form\GoogleAdwordsPathConfigForm",
 *       "edit" = "Drupal\google_adwords_path\Form\GoogleAdwordsPathConfigForm",
 *       "delete" = "Drupal\google_adwords_path\Form\GoogleAdwordsPathConfigDeleteForm"
 *     }
 *   },
 *   config_prefix = "google_adwords_path_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "uuid",
 *     "id",
 *     "label",
 *     "enabled",
 *     "conversion_id",
 *     "language",
 *     "format",
 *     "colour",
 *     "paths"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/google_adwords/path/{google_adwords_path_config}",
 *     "edit-form" = "/admin/config/system/google_adwords/path/{google_adwords_path_config}/edit",
 *     "delete-form" = "/admin/config/system/google_adwords/path/{google_adwords_path_config}/delete",
 *     "collection" = "/admin/config/system/google_adwords/path"
 *   }
 * )
 */
class GoogleAdwordsPathConfig extends ConfigEntityBase implements ConfigEntityInterface {
  /**
   * The Google AdWords Path Config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Google AdWords Path Config label.
   *
   * @var string
   */
  protected $label;

}
