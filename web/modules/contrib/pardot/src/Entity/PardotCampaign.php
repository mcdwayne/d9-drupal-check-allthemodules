<?php

namespace Drupal\pardot\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Pardot Campaign entity.
 *
 * @ingroup Pardot
 *
 * @ConfigEntityType(
 *   id = "pardot_campaign",
 *   label = @Translation("Pardot Campaign"),
 *   admin_permission = "administer pardot settings",
 *   handlers = {
 *     "access" = "Drupal\pardot\PardotCampaignAccessController",
 *     "list_builder" = "Drupal\pardot\Controller\PardotCampaignListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pardot\Form\PardotCampaignAddForm",
 *       "edit" = "Drupal\pardot\Form\PardotCampaignEditForm",
 *       "delete" = "Drupal\pardot\Form\PardotCampaignDeleteForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/pardot/campaigns/{pardot_campagin}",
 *     "delete-form" = "/admin/config/services/pardot/campaigns/{pardot_campagin}/delete"
 *   }
 * )
 */
class PardotCampaign extends ConfigEntityBase {

  /**
   * The Pardot Campaign ID.
   *
   * @var string
   */
  public $id;

  /**
   * The campaign UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The Pardot Campaign label.
   *
   * @var string
   */
  public $label;

  /**
   * The Pardot Campaign ID.
   *
   * @var string
   */
  public $campaign_id;

  /**
   * The Pardot Campaign path condition.
   *
   * @var string
   */
  public $pages;
}
