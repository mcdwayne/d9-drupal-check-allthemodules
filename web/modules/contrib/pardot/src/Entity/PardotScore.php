<?php

namespace Drupal\pardot\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Pardot Score entity.
 *
 * @ingroup Pardot
 *
 * @ConfigEntityType(
 *   id = "pardot_score",
 *   label = @Translation("Pardot Score"),
 *   admin_permission = "administer pardot settings",
 *   handlers = {
 *     "access" = "Drupal\pardot\PardotScoreAccessController",
 *     "list_builder" = "Drupal\pardot\Controller\PardotScoreListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pardot\Form\PardotScoreAddForm",
 *       "edit" = "Drupal\pardot\Form\PardotScoreEditForm",
 *       "delete" = "Drupal\pardot\Form\PardotScoreDeleteForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/pardot/campaigns/{pardot_score}",
 *     "delete-form" = "/admin/config/services/pardot/campaigns/{pardot_score}/delete"
 *   }
 * )
 */
class PardotScore extends ConfigEntityBase {

  /**
   * The Pardot Score ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Score UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The Pardot Score label.
   *
   * @var string
   */
  public $label;

  /**
   * The Pardot Score Value.
   *
   * @var string
   */
  public $score_value;

  /**
   * The Pardot Score path condition.
   *
   * @var string
   */
  public $pages;
}
