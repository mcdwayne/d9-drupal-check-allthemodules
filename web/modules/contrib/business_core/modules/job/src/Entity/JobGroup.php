<?php

namespace Drupal\job\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\job\JobGroupInterface;

/**
 * Defines the Job group configuration entity.
 *
 * @ConfigEntityType(
 *   id = "job_group",
 *   label = @Translation("Job group"),
 *   handlers = {
 *     "access" = "Drupal\job\JobGroupAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\job\JobGroupForm",
 *       "delete" = "Drupal\job\Form\JobGroupDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\job\JobGroupListBuilder",
 *   },
 *   admin_permission = "administer job groups",
 *   config_prefix = "group",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/resource/job/group/{job_group}",
 *     "delete-form" = "/admin/resource/job/group/{job_group}/delete",
 *     "collection" = "/admin/resource/job/group",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class JobGroup extends ConfigEntityBase implements JobGroupInterface {

  /**
   * The machine name of this Job group.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the Job group.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Job group.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
