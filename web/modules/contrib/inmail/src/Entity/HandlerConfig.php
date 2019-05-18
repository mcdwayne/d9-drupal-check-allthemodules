<?php

namespace Drupal\inmail\Entity;

use Drupal\inmail\HandlerConfigInterface;

/**
 * MimeMessage handler configuration entity.
 *
 * This entity type is for storing the configuration of a handler plugin.
 *
 * @ingroup handler
 *
 * @ConfigEntityType(
 *   id = "inmail_handler",
 *   label = @Translation("MimeMessage handler"),
 *   admin_permission = "administer inmail",
 *   config_prefix = "handler",
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\inmail\Form\HandlerConfigurationForm"
 *     },
 *     "list_builder" = "Drupal\inmail\HandlerListBuilder"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "configuration",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/inmail/analyzers/{inmail_handler}",
 *     "enable" = "/admin/config/system/inmail/analyzers/{inmail_handler}/enable",
 *     "disable" = "/admin/config/system/inmail/analyzers/{inmail_handler}/disable"
 *   }
 * )
 */
class HandlerConfig extends PluginConfigEntity implements HandlerConfigInterface {
  // @todo Implement HandlerConfig::calculateDependencies() https://www.drupal.org/node/2379929

  /**
   * The Inmail plugin type.
   *
   * @var string
   */
  protected $pluginType = 'handler';

}
