<?php

namespace Drupal\wsdata_field\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Web Service Call entity.
 *
 * @ConfigEntityType(
 *   id = "wsfield_config",
 *   label = @Translation("wsfield configurations"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\wsdata_field\Form\WSFieldConfigForm",
 *       "edit" = "Drupal\wsdata_field\Form\WSFieldConfigForm",
 *     },
 *   },
 *   config_prefix = "wsfield_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class WSFieldConfig extends ConfigEntityBase {
  /**
   * The Web Service Call ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Webservice field configurations.
   *
   * @var string
   */
  public $wscall;
  public $replacements;
  public $returnToken;
  public $data;


  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
  }
}
