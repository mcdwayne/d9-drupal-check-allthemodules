<?php

namespace Drupal\spectra_connect\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\spectra_connect\SpectraConnectInterface;

/**
 * Defines the Example entity.
 *
 * @ConfigEntityType(
 *   id = "spectra_connect",
 *   label = @Translation("Spectra Connect Entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\spectra_connect\Entity\Controller\SpectraConnectListBuilder",
 *     "form" = {
 *       "add" = "Drupal\spectra_connect\Form\SpectraConnectForm",
 *       "edit" = "Drupal\spectra_connect\Form\SpectraConnectForm",
 *       "delete" = "Drupal\spectra_connect\Form\SpectraConnectDeleteForm",
 *     }
 *   },
 *   config_prefix = "spectra_connect",
 *   admin_permission = "administer spectra_connect",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/spectra_connect/list",
 *     "edit-form" = "/admin/structure/spectra_connect/{example}",
 *     "delete-form" = "/admin/structure/spectra_connect/{example}/delete",
 *   }
 * )
 */
class SpectraConnect extends ConfigEntityBase implements SpectraConnectInterface {

  /**
   * The Example ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Example label.
   *
   * @var string
   */
  public $label;

}
