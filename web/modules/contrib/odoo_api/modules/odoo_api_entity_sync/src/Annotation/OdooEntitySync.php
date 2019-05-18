<?php

namespace Drupal\odoo_api_entity_sync\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Odoo entity sync item annotation object.
 *
 * @see \Drupal\odoo_api_entity_sync\Plugin\EntitySyncPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class OdooEntitySync extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity type.
   *
   * @var string
   */
  public $entityType;

  /**
   * Odoo model name.
   *
   * @var string
   */
  public $odooModel;

  /**
   * Export type. May be used to export same entity multiple times.
   *
   * An example use case is exporting user as contact + company.
   *
   * @var string
   */
  public $exportType = 'default';

}
