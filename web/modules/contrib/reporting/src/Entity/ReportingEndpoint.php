<?php

namespace Drupal\reporting\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Reporting Endpoint Entity.
 *
 * @ConfigEntityType(
 *   id = "reporting_endpoint",
 *   label = @Translation("Reporting Endpoint"),
 *   handlers = {
 *     "list_builder" = "Drupal\reporting\Controller\ReportingEndpointListBuilder",
 *     "form" = {
 *       "add" = "Drupal\reporting\Form\ReportingEndpointForm",
 *       "edit" = "Drupal\reporting\Form\ReportingEndpointForm",
 *       "delete" = "Drupal\reporting\Form\ReportingEndpointDeleteForm",
 *     }
 *   },
 *   config_prefix = "reporting_endpoint",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/reporting/{reporting_endpoint}",
 *     "delete-form" = "/admin/config/system/reporting/{reporting_endpoint}/delete",
 *     "collection" = "/admin/config/system/reporting",
 *     "log" = "/system/reporting/{reporting_endpoint}",
 *   }
 * )
 */
class ReportingEndpoint extends ConfigEntityBase implements ReportingEndpointInterface {

  /**
   * The reporting endpoint id.
   *
   * @var string
   */
  public $id;

  /**
   * The reporting endpoint label.
   *
   * @var string
   */
  public $label;

}
