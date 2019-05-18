<?php

namespace Drupal\cloudconvert\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the CloudConvert Task type entity.
 *
 * @ConfigEntityType(
 *   id = "cloudconvert_task_type",
 *   label = @Translation("CloudConvert Task type"),
 *   config_prefix = "cloudconvert_task_type",
 *   admin_permission = "administer cloudconvert settings",
 *   bundle_of = "cloudconvert_task",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class CloudConvertTaskType extends ConfigEntityBundleBase implements CloudConvertTaskTypeInterface {

  /**
   * The CloudConvert Task type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The CloudConvert Task type label.
   *
   * @var string
   */
  protected $label;

  /**
   * Finish Method.
   *
   * @var string
   */
  protected $finish_method;

  /**
   * {@inheritdoc}
   */
  public function getFinishMethod() {
    return $this->finish_method;
  }

  /**
   * {@inheritdoc}
   */
  public function setFinishMethod($finishMethod) {
    $this->finish_method = $finishMethod;
  }

}
